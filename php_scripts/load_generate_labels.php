<?php
/**
 * Generate All Labels for a Load and Populate Print Queue (with Smart Expiry Logic)
 *
 * This version implements the new rule-based expiry date calculation.
 * It intelligently determines the expiry date for each item based on
 * its master data, the master data of its components (for sets), or the global
 * default, ensuring maximum safety and accuracy.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once '../config.php';

// --- Authorization & CSRF Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../manage_loads.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    $loadId = $_POST['load_id'] ?? null;
    $redirectUrl = $loadId ? "../load_detail.php?load_id=" . $loadId : "../manage_loads.php";
    header("Location: " . $redirectUrl);
    exit;
}
$loggedInUserId = $_SESSION['user_id'] ?? null;
$globalDefaultExpiryDays = (int)($app_settings['default_expiry_days'] ?? 30);

$loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);
if (!$loadId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Muatan tidak valid.'];
    header("Location: ../manage_loads.php");
    exit;
}

$conn = connectToDatabase();
if (!$conn) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
    header("Location: ../load_detail.php?load_id=" . $loadId);
    exit;
}

$conn->begin_transaction();
try {
    // 1. Dapatkan informasi muatan utama
    $stmtLoadInfo = $conn->prepare("SELECT load_name, cycle_id, destination_department_id FROM sterilization_loads WHERE load_id = ? AND status = 'selesai'");
    $stmtLoadInfo->bind_param("i", $loadId);
    $stmtLoadInfo->execute();
    $resultLoadInfo = $stmtLoadInfo->get_result();
    if (!($loadInfo = $resultLoadInfo->fetch_assoc())) {
        throw new Exception("Muatan tidak ditemukan atau statusnya bukan 'Selesai'.");
    }
    $cycleId = $loadInfo['cycle_id'];
    $destinationDepartmentId = $loadInfo['destination_department_id'];
    $stmtLoadInfo->close();

    // 2. Ambil semua item dari muatan beserta snapshotnya
    $stmtItems = $conn->prepare("SELECT item_id, item_type, item_snapshot FROM sterilization_load_items WHERE load_id = ?");
    $stmtItems->bind_param("i", $loadId);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    $itemsToProcess = [];
    $setIds = [];
    $instrumentIds = []; // Kumpulkan semua instrument_id yang relevan
    while ($item = $resultItems->fetch_assoc()) {
        $itemsToProcess[] = $item;
        if ($item['item_type'] === 'set') {
            $setIds[] = $item['item_id'];
            // Ambil instrument_id dari snapshot
            if (!empty($item['item_snapshot'])) {
                $snapshot = json_decode($item['item_snapshot'], true);
                if (is_array($snapshot)) {
                    foreach ($snapshot as $snapItem) {
                        $instrumentIds[] = (int)$snapItem['instrument_id'];
                    }
                }
            }
        } else {
            $instrumentIds[] = $item['item_id'];
        }
    }
    $stmtItems->close();

    if (empty($itemsToProcess)) {
        throw new Exception("Tidak ada item di dalam muatan ini untuk dibuatkan label.");
    }

    // Pastikan ID unik
    $instrumentIds = array_unique(array_filter($instrumentIds));
    $setIds = array_unique(array_filter($setIds));

    // 3. (OPTIMISASI) Ambil semua data master yang relevan dalam beberapa query saja
    $masterData = [
        'instrument' => [], // Kunci diubah agar cocok dengan item_type
        'set' => []
    ];

    // Ambil detail instrumen (nama dan masa kedaluwarsa)
    if (!empty($instrumentIds)) {
        $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
        $stmtInst = $conn->prepare("SELECT instrument_id, instrument_name, expiry_in_days FROM instruments WHERE instrument_id IN ($placeholders)");
        $stmtInst->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
        $stmtInst->execute();
        $resInst = $stmtInst->get_result();
        while ($row = $resInst->fetch_assoc()) {
            $masterData['instrument'][$row['instrument_id']] = $row;
        }
        $stmtInst->close();
    }

    // Ambil detail set (nama dan masa kedaluwarsa)
    if (!empty($setIds)) {
        $placeholders = implode(',', array_fill(0, count($setIds), '?'));
        $stmtSet = $conn->prepare("SELECT set_id, set_name, expiry_in_days FROM instrument_sets WHERE set_id IN ($placeholders)");
        $stmtSet->bind_param(str_repeat('i', count($setIds)), ...$setIds);
        $stmtSet->execute();
        $resSet = $stmtSet->get_result();
        while ($row = $resSet->fetch_assoc()) {
            $masterData['set'][$row['set_id']] = $row;
        }
        $stmtSet->close();
    }

    // 4. Siapkan statement SQL untuk loop
    $sqlInsertLabel = "INSERT INTO sterilization_records
                        (label_unique_id, load_id, cycle_id, item_id, item_type, label_title,
                         created_by_user_id, expiry_date, status, label_items_snapshot, print_status, destination_department_id)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, 'pending', ?)";
    $stmtInsert = $conn->prepare($sqlInsertLabel);

    $sqlInsertQueue = "INSERT INTO print_queue (record_id, load_id) VALUES (?, ?)";
    $stmtQueue = $conn->prepare($sqlInsertQueue);

    $createdCount = 0;

    // 5. Loop untuk membuat label dengan LOGIKA KEDALUWARSA CERDAS
    foreach ($itemsToProcess as $item) {
        $itemId = (int)$item['item_id'];
        $itemType = $item['item_type'];
        $itemSnapshotJson = $item['item_snapshot'] ?? null;

        $itemMaster = $masterData[$itemType][$itemId] ?? null;
        if (!$itemMaster) continue;

        $labelTitle = $itemMaster[$itemType . '_name'] ?? 'Item tidak dikenal';

        // === PERBAIKAN LOGIKA KEDALUWARSA DIMULAI DI SINI ===
        $daysUntilExpiry = $globalDefaultExpiryDays; // Selalu mulai dengan default global untuk setiap item.

        if ($itemType === 'instrument') {
            // Jika instrumen, gunakan masa kedaluwarsanya jika ada, jika tidak, tetap gunakan default global.
            $daysUntilExpiry = (int)($itemMaster['expiry_in_days'] ?? $globalDefaultExpiryDays);
        } elseif ($itemType === 'set') {
            $setSpecificExpiry = (int)($itemMaster['expiry_in_days'] ?? 0);
            $instrumentMinExpiry = null;

            // Periksa masa kedaluwarsa terpendek dari instrumen di dalamnya
            if (!empty($itemSnapshotJson)) {
                $snapshot = json_decode($itemSnapshotJson, true);
                if (is_array($snapshot) && !empty($snapshot)) {
                    $expiryValues = [];
                    foreach ($snapshot as $snapItem) {
                        $instrumentInSet = $masterData['instrument'][(int)$snapItem['instrument_id']] ?? null;
                        $expiryValues[] = (int)($instrumentInSet['expiry_in_days'] ?? $globalDefaultExpiryDays);
                    }
                    if (!empty($expiryValues)) {
                        $instrumentMinExpiry = min($expiryValues);
                    }
                }
            }

            // Terapkan aturan prioritas dengan benar
            if ($setSpecificExpiry > 0) {
                // Prioritas 1: Gunakan pengaturan spesifik set jika ada.
                $daysUntilExpiry = $setSpecificExpiry;
            } elseif ($instrumentMinExpiry !== null) {
                // Prioritas 2: Gunakan masa terpendek dari instrumen di dalamnya.
                $daysUntilExpiry = $instrumentMinExpiry;
            }
            // Prioritas 3: Jika tidak ada keduanya, nilai akan tetap menjadi default global yang sudah diatur di awal loop.
        }
        // === AKHIR PERBAIKAN ===

        $expiryDate = (new DateTime())->modify("+" . $daysUntilExpiry . " days")->format('Y-m-d H:i:s');

        $maxAttempts = 5;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $labelUniqueId = strtoupper(bin2hex(random_bytes(4)));

            $stmtInsert->bind_param("siiississi", $labelUniqueId, $loadId, $cycleId, $itemId, $itemType, $labelTitle, $loggedInUserId, $expiryDate, $itemSnapshotJson, $destinationDepartmentId);

            if ($stmtInsert->execute()) {
                $newRecordId = $stmtInsert->insert_id;

                $stmtQueue->bind_param("ii", $newRecordId, $loadId);
                if (!$stmtQueue->execute()) {
                    throw new Exception("Gagal menambahkan label (ID: $newRecordId) ke antrean cetak: " . $stmtQueue->error);
                }

                $createdCount++;
                break;
            } else {
                if ($conn->errno === 1062) {
                    if ($attempt === $maxAttempts - 1) throw new Exception("Gagal menghasilkan ID label unik setelah $maxAttempts percobaan.");
                    continue;
                } else {
                    throw new Exception("Gagal membuat label untuk item ID $itemId: " . $stmtInsert->error);
                }
            }
        }
    }

    $stmtInsert->close();
    $stmtQueue->close();
    $conn->commit();

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => "$createdCount label berhasil dibuat untuk muatan '" . htmlspecialchars($loadInfo['load_name']) . "' dan ditambahkan ke antrean cetak."];
    log_activity('GENERATE_LABELS', $loggedInUserId, "Membuat $createdCount label untuk Muatan ID: $loadId.");

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan: " . $e->getMessage()];
    header("Location: ../load_detail.php?load_id=" . $loadId);
    exit;
} finally {
    if ($conn) {
        $conn->close();
    }
}

header("Location: ../print_queue.php?load_id=" . $loadId);
exit;
