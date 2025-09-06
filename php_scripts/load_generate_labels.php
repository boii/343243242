<?php
/**
 * Generate All Labels for a Load and Populate Print Queue (with Smart Expiry Logic)
 *
 * This version implements the new rule-based expiry date calculation using ExpiryCalculator.
 * It intelligently determines the expiry date for each item based on
 * packaging type, item-specific rules, and global defaults.
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
// PERUBAHAN: Sertakan class ExpiryCalculator
require_once '../app/ExpiryCalculator.php';

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

// PERUBAHAN: Buat instance dari ExpiryCalculator
$expiryCalculator = new ExpiryCalculator($conn, $globalDefaultExpiryDays);

$conn->begin_transaction();
try {
    // 1. Dapatkan informasi muatan utama
    // PERUBAHAN: Ambil juga packaging_type_id
    $stmtLoadInfo = $conn->prepare(
        "SELECT load_name, cycle_id, destination_department_id, packaging_type_id 
         FROM sterilization_loads 
         WHERE load_id = ? AND status = 'selesai'"
    );
    $stmtLoadInfo->bind_param("i", $loadId);
    $stmtLoadInfo->execute();
    $resultLoadInfo = $stmtLoadInfo->get_result();
    if (!($loadInfo = $resultLoadInfo->fetch_assoc())) {
        throw new Exception("Muatan tidak ditemukan atau statusnya bukan 'Selesai'.");
    }
    $cycleId = $loadInfo['cycle_id'];
    $destinationDepartmentId = $loadInfo['destination_department_id'];
    // PERUBAHAN: Simpan packaging_type_id
    $packagingTypeId = (int)$loadInfo['packaging_type_id'];
    $stmtLoadInfo->close();
    
    // Validasi apakah packaging type dipilih
    if (empty($packagingTypeId)) {
        throw new Exception("Jenis kemasan belum diatur untuk muatan ini. Proses tidak dapat dilanjutkan.");
    }


    // 2. Ambil semua item dari muatan beserta snapshotnya
    $stmtItems = $conn->prepare("SELECT item_id, item_type, item_snapshot FROM sterilization_load_items WHERE load_id = ?");
    $stmtItems->bind_param("i", $loadId);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    $itemsToProcess = $resultItems->fetch_all(MYSQLI_ASSOC);
    $stmtItems->close();

    if (empty($itemsToProcess)) {
        throw new Exception("Tidak ada item di dalam muatan ini untuk dibuatkan label.");
    }
    
    // 3. OPTIMISASI: Ambil nama item dalam satu query untuk efisiensi
    // (ExpiryCalculator akan menangani query expiry secara internal)
    $itemNames = [];
    $instrumentIds = array_column(array_filter($itemsToProcess, fn($i) => $i['item_type'] === 'instrument'), 'item_id');
    $setIds = array_column(array_filter($itemsToProcess, fn($i) => $i['item_type'] === 'set'), 'item_id');

    if (!empty($instrumentIds)) {
        $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
        $stmt = $conn->prepare("SELECT instrument_id, instrument_name FROM instruments WHERE instrument_id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $itemNames['instrument'][$row['instrument_id']] = $row['instrument_name'];
        }
        $stmt->close();
    }
    if (!empty($setIds)) {
        $placeholders = implode(',', array_fill(0, count($setIds), '?'));
        $stmt = $conn->prepare("SELECT set_id, set_name FROM instrument_sets WHERE set_id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($setIds)), ...$setIds);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $itemNames['set'][$row['set_id']] = $row['set_name'];
        }
        $stmt->close();
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

    // 5. Loop untuk membuat label dengan LOGIKA BARU
    foreach ($itemsToProcess as $item) {
        $itemId = (int)$item['item_id'];
        $itemType = $item['item_type'];
        $itemSnapshotJson = $item['item_snapshot'] ?? null;
        
        $labelTitle = $itemNames[$itemType][$itemId] ?? 'Item tidak dikenal';
        
        // PERUBAHAN UTAMA: Gunakan ExpiryCalculator
        $expiryDate = $expiryCalculator->getExpiryDate($itemId, $itemType, $packagingTypeId, $itemSnapshotJson);
        
        // Coba buat label dengan ID unik
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
                break; // Berhasil, keluar dari loop percobaan
            } else {
                if ($conn->errno === 1062) { // Error duplikat kunci
                    if ($attempt === $maxAttempts - 1) throw new Exception("Gagal menghasilkan ID label unik setelah $maxAttempts percobaan.");
                    continue; // Coba lagi dengan ID baru
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

// Arahkan ke halaman antrean cetak setelah berhasil
header("Location: ../print_queue.php?load_id=" . $loadId);
exit;