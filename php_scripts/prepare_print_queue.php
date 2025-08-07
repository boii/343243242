<?php
/**
 * Prepare Print Queue Script (Smart Label Generator with Print Count)
 *
 * This script now increments a print_count for each label to enable
 * reprint watermarking.
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
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
    $stmtLoadInfo = $conn->prepare("SELECT cycle_id, destination_department_id FROM sterilization_loads WHERE load_id = ? AND status = 'selesai'");
    $stmtLoadInfo->bind_param("i", $loadId);
    $stmtLoadInfo->execute();
    $resultLoadInfo = $stmtLoadInfo->get_result();
    if (!($loadInfo = $resultLoadInfo->fetch_assoc())) {
        throw new Exception("Muatan tidak ditemukan atau statusnya bukan 'Selesai'.");
    }
    $cycleId = $loadInfo['cycle_id'];
    $destinationDepartmentId = $loadInfo['destination_department_id'];
    $stmtLoadInfo->close();

    $stmtItems = $conn->prepare("SELECT load_item_id, item_id, item_type, item_snapshot FROM sterilization_load_items WHERE load_id = ?");
    $stmtItems->bind_param("i", $loadId);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    $itemsToProcess = $resultItems->fetch_all(MYSQLI_ASSOC);
    $stmtItems->close();

    if (empty($itemsToProcess)) {
        throw new Exception("Tidak ada item di dalam muatan ini untuk dibuatkan label.");
    }

    $existingLabels = [];
    $stmtExisting = $conn->prepare("SELECT record_id, load_item_id FROM sterilization_records WHERE load_id = ? AND load_item_id IS NOT NULL");
    $stmtExisting->bind_param("i", $loadId);
    $stmtExisting->execute();
    $resultExisting = $stmtExisting->get_result();
    while($row = $resultExisting->fetch_assoc()){
        $existingLabels[(int)$row['load_item_id']] = (int)$row['record_id'];
    }
    $stmtExisting->close();

    $instrumentIds = [];
    $setIds = [];
    foreach ($itemsToProcess as $item) {
        if ($item['item_type'] === 'set') {
             $setIds[] = $item['item_id'];
             if (!empty($item['item_snapshot'])) {
                $snapshot = json_decode($item['item_snapshot'], true);
                if (is_array($snapshot)) {
                    $instrumentIds = array_merge($instrumentIds, array_column($snapshot, 'instrument_id'));
                }
             }
        } else {
            $instrumentIds[] = $item['item_id'];
        }
    }
    
    $instrumentIds = array_unique(array_filter($instrumentIds));
    $setIds = array_unique(array_filter($setIds));

    $masterData = ['instrument' => [], 'set' => []];
    if (!empty($instrumentIds)) {
        $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
        $stmtMasterInst = $conn->prepare("SELECT instrument_id, instrument_name, expiry_in_days FROM instruments WHERE instrument_id IN ($placeholders)");
        $stmtMasterInst->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
        $stmtMasterInst->execute();
        $resInst = $stmtMasterInst->get_result();
        while ($row = $resInst->fetch_assoc()) $masterData['instrument'][$row['instrument_id']] = $row;
        $stmtMasterInst->close();
    }
    if (!empty($setIds)) {
        $placeholders = implode(',', array_fill(0, count($setIds), '?'));
        $stmtMasterSet = $conn->prepare("SELECT set_id, set_name, expiry_in_days FROM instrument_sets WHERE set_id IN ($placeholders)");
        $stmtMasterSet->bind_param(str_repeat('i', count($setIds)), ...$setIds);
        $stmtMasterSet->execute();
        $resSet = $stmtMasterSet->get_result();
        while ($row = $resSet->fetch_assoc()) $masterData['set'][$row['set_id']] = $row;
        $stmtMasterSet->close();
    }
    
    $sqlInsertLabel = "INSERT INTO sterilization_records (label_unique_id, load_id, cycle_id, item_id, item_type, label_title, created_by_user_id, expiry_date, status, label_items_snapshot, print_status, destination_department_id, load_item_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, 'pending', ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsertLabel);

    $labelsForQueue = [];
    $newlyCreatedCount = 0;

    foreach ($itemsToProcess as $item) {
        $loadItemId = (int)$item['load_item_id'];
        if (isset($existingLabels[$loadItemId])) {
            $labelsForQueue[] = $existingLabels[$loadItemId];
        } else {
            $itemMaster = $masterData[$item['item_type']][$item['item_id']] ?? null;
            if (!$itemMaster) continue;
            
            $labelTitle = $itemMaster[$item['item_type'] . '_name'] ?? 'Item tidak dikenal';
            $daysUntilExpiry = $globalDefaultExpiryDays;

            if ($item['item_type'] === 'instrument') {
                $daysUntilExpiry = (int)($itemMaster['expiry_in_days'] ?? $globalDefaultExpiryDays);
            } elseif ($item['item_type'] === 'set') {
                if (isset($itemMaster['expiry_in_days']) && (int)$itemMaster['expiry_in_days'] > 0) {
                    $daysUntilExpiry = (int)$itemMaster['expiry_in_days'];
                } else {
                    if (!empty($item['item_snapshot'])) {
                        $snapshot = json_decode($item['item_snapshot'], true);
                        if (is_array($snapshot) && !empty($snapshot)) {
                            $expiryValues = [];
                            foreach ($snapshot as $snapItem) {
                                $instrumentInSet = $masterData['instrument'][(int)$snapItem['instrument_id']] ?? null;
                                $expiryValues[] = (int)($instrumentInSet['expiry_in_days'] ?? $globalDefaultExpiryDays);
                            }
                            if (!empty($expiryValues)) {
                                $daysUntilExpiry = min($expiryValues);
                            }
                        }
                    }
                }
            }
            
            $expiryDate = (new DateTime())->modify("+" . $daysUntilExpiry . " days")->format('Y-m-d H:i:s');
            
            $maxAttempts = 5;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $labelUniqueId = strtoupper(bin2hex(random_bytes(4)));
                $stmtInsert->bind_param("siiississii", $labelUniqueId, $loadId, $cycleId, $item['item_id'], $item['item_type'], $labelTitle, $loggedInUserId, $expiryDate, $item['item_snapshot'], $destinationDepartmentId, $loadItemId);
                
                if ($stmtInsert->execute()) {
                    $newRecordId = $stmtInsert->insert_id;
                    if ($newRecordId > 0) {
                        $labelsForQueue[] = $newRecordId;
                        $newlyCreatedCount++;
                    }
                    break;
                } else {
                    if ($conn->errno !== 1062) {
                        throw new Exception("Gagal membuat label: " . $stmtInsert->error);
                    }
                    if ($attempt === $maxAttempts - 1) {
                         throw new Exception("Gagal menghasilkan ID label unik setelah $maxAttempts percobaan.");
                    }
                }
            }
        }
    }
    $stmtInsert->close();
    
    // PERUBAHAN: Menambah jumlah cetak untuk setiap label yang akan dicetak
    if (!empty($labelsForQueue)) {
        $placeholders = implode(',', array_fill(0, count($labelsForQueue), '?'));
        $stmtIncrementCount = $conn->prepare("UPDATE sterilization_records SET print_count = print_count + 1 WHERE record_id IN ($placeholders)");
        $stmtIncrementCount->bind_param(str_repeat('i', count($labelsForQueue)), ...$labelsForQueue);
        $stmtIncrementCount->execute();
        $stmtIncrementCount->close();
    }
    
    $stmtClearQueue = $conn->prepare("DELETE FROM print_queue WHERE load_id = ?");
    $stmtClearQueue->bind_param("i", $loadId);
    $stmtClearQueue->execute();
    $stmtClearQueue->close();

    if (!empty($labelsForQueue)) {
        $sqlInsertQueue = "INSERT INTO print_queue (record_id, load_id) VALUES (?, ?)";
        $stmtQueue = $conn->prepare($sqlInsertQueue);
        foreach ($labelsForQueue as $recordId) {
            $stmtQueue->bind_param("ii", $recordId, $loadId);
            $stmtQueue->execute();
        }
        $stmtQueue->close();
    }

    $conn->commit();
    $message = "Antrean cetak siap. " . ($newlyCreatedCount > 0 ? "$newlyCreatedCount label baru dibuat." : "Semua label sudah ada dan ditambahkan kembali ke antrean.");
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => $message];

    if ($newlyCreatedCount > 0) {
        log_activity('GENERATE_LABELS', $loggedInUserId, "Membuat $newlyCreatedCount label baru untuk Muatan ID: $loadId.");
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan: " . $e->getMessage()];
    header("Location: ../load_detail.php?load_id=" . $loadId);
    exit;
} finally {
    if ($conn) $conn->close();
}

header("Location: ../print_queue.php?load_id=" . $loadId);
exit;