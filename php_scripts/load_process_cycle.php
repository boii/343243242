<?php
/**
 * Script untuk memproses muatan yang dipilih dan menautkannya ke siklus sterilisasi baru.
 * Ini juga menghasilkan label unik untuk setiap item di dalam muatan dengan tanggal kadaluarsa yang tepat.
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
require_once '../app/helpers.php'; // Memastikan fungsi helper dimuat

// --- Authorization & CSRF Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Silakan login.'];
    header("Location: ../login.php");
    exit;
}
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
    header("Location: ../manage_loads.php");
    exit;
}

$loggedInUserId = $_SESSION['user_id'];
$loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);
$machineId = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
$cycleNumber = trim($_POST['cycle_number'] ?? '');
$cycleDate = trim($_POST['cycle_date'] ?? date('Y-m-d H:i:s'));

if (!$loadId || !$machineId || empty($cycleNumber) || empty($cycleDate)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Data muatan, mesin, dan nomor siklus wajib diisi.'];
    header("Location: ../load_detail.php?load_id=" . $loadId);
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
    // 1. Perbarui status muatan menjadi 'berjalan'
    $stmtLoad = $conn->prepare("UPDATE sterilization_loads SET status = 'berjalan', machine_id = ?, updated_at = NOW() WHERE load_id = ? AND status = 'persiapan'");
    if (!$stmtLoad) {
        throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
    }
    $stmtLoad->bind_param("ii", $machineId, $loadId);
    if (!$stmtLoad->execute()) {
        throw new Exception("Gagal memperbarui status muatan: " . $stmtLoad->error);
    }
    if ($stmtLoad->affected_rows === 0) {
        throw new Exception("Muatan tidak ditemukan atau tidak dalam status 'persiapan'.");
    }
    $stmtLoad->close();

    // 2. Buat siklus sterilisasi baru
    $stmtCycle = $conn->prepare("INSERT INTO sterilization_cycles (machine_name, cycle_number, cycle_date, operator_user_id) SELECT machine_name, ?, ?, ? FROM machines WHERE machine_id = ?");
    if (!$stmtCycle) {
        throw new Exception("Gagal mempersiapkan statement siklus: " . $conn->error);
    }
    $stmtCycle->bind_param("ssii", $cycleNumber, $cycleDate, $loggedInUserId, $machineId);
    if (!$stmtCycle->execute()) {
        throw new Exception("Gagal membuat siklus sterilisasi: " . $stmtCycle->error);
    }
    $newCycleId = $conn->insert_id;
    $stmtCycle->close();

    // 3. Tautkan muatan ke siklus yang baru dibuat
    $stmtUpdateLoadCycle = $conn->prepare("UPDATE sterilization_loads SET cycle_id = ?, status = 'menunggu_validasi' WHERE load_id = ?");
    if (!$stmtUpdateLoadCycle) {
        throw new Exception("Gagal mempersiapkan statement tautan: " . $conn->error);
    }
    $stmtUpdateLoadCycle->bind_param("ii", $newCycleId, $loadId);
    if (!$stmtUpdateLoadCycle->execute()) {
        throw new Exception("Gagal menautkan muatan ke siklus: " . $stmtUpdateLoadCycle->error);
    }
    $stmtUpdateLoadCycle->close();

    // 4. Ambil packaging_type_id dari muatan untuk perhitungan kedaluwarsa
    $packagingTypeId = null;
    $stmtLoadPackaging = $conn->prepare("SELECT packaging_type_id FROM sterilization_loads WHERE load_id = ?");
    $stmtLoadPackaging->bind_param("i", $loadId);
    $stmtLoadPackaging->execute();
    $resultPackaging = $stmtLoadPackaging->get_result()->fetch_assoc();
    if ($resultPackaging) {
        $packagingTypeId = $resultPackaging['packaging_type_id'];
    }
    $stmtLoadPackaging->close();

    // 5. Salin setiap item muatan ke dalam tabel sterilization_records
    $stmtItems = $conn->prepare("SELECT load_item_id, item_id, item_type, quantity FROM sterilization_load_items WHERE load_id = ?");
    $stmtItems->bind_param("i", $loadId);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();

    $stmtInsertRecord = $conn->prepare(
        "INSERT INTO sterilization_records (label_unique_id, item_id, item_type, label_title, created_by_user_id, cycle_id, load_id, destination_department_id, load_item_id, expiry_date, status, label_items_snapshot)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)"
    );

    $recordsCreated = 0;
    while ($item = $resultItems->fetch_assoc()) {
        $labelTitle = ''; // Dapatkan nama item dari snapshot atau tabel master
        if ($item['item_type'] === 'set') {
            $stmtGetName = $conn->prepare("SELECT set_name FROM instrument_sets WHERE set_id = ?");
            $stmtGetName->bind_param("i", $item['item_id']);
            $stmtGetName->execute();
            $labelTitle = $stmtGetName->get_result()->fetch_assoc()['set_name'];
            $stmtGetName->close();
        } else {
            $stmtGetName = $conn->prepare("SELECT instrument_name FROM instruments WHERE instrument_id = ?");
            $stmtGetName->bind_param("i", $item['item_id']);
            $stmtGetName->execute();
            $labelTitle = $stmtGetName->get_result()->fetch_assoc()['instrument_name'];
            $stmtGetName->close();
        }

        // Ambil snapshot item (jika ada)
        $itemSnapshot = null;
        if ($item['item_type'] === 'set') {
            $stmtGetSnapshot = $conn->prepare("SELECT item_snapshot FROM sterilization_load_items WHERE load_item_id = ?");
            $stmtGetSnapshot->bind_param("i", $item['load_item_id']);
            $stmtGetSnapshot->execute();
            $snapshotData = $stmtGetSnapshot->get_result()->fetch_assoc();
            $itemSnapshot = $snapshotData['item_snapshot'];
            $stmtGetSnapshot->close();
        }

        // MENGHITUNG TANGGAL KADALUWARSA MENGGUNAKAN FUNGSI BARU
        $expiryDate = calculateExpiryDate($conn, $item['item_id'], $item['item_type'], $packagingTypeId);

        for ($i = 0; $i < $item['quantity']; $i++) {
            $uniqueId = generateUniqueLabelId();
            $stmtInsertRecord->bind_param(
                "sissiiisss", 
                $uniqueId, 
                $item['item_id'], 
                $item['item_type'], 
                $labelTitle, 
                $loggedInUserId, 
                $newCycleId, 
                $loadId, 
                $loadData['destination_department_id'], 
                $item['load_item_id'], 
                $expiryDate,
                $itemSnapshot
            );
            $stmtInsertRecord->execute();
            $recordsCreated++;
        }
    }

    $stmtInsertRecord->close();
    $conn->commit();

    log_activity('PROCESS_LOAD', $loggedInUserId, "Muatan (ID: {$loadId}) diproses dan ditautkan ke Siklus baru (ID: {$newCycleId}) dengan nomor {$cycleNumber}.", 'load', $loadId);

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Muatan berhasil diproses dan siklus baru #{$cycleNumber} telah dimulai."];
    header("Location: ../cycle_validation.php?cycle_id=" . $newCycleId);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan saat memproses muatan: " . $e->getMessage()];
    header("Location: ../load_detail.php?load_id=" . $loadId);
    exit;
} finally {
    $conn->close();
}

/**
 * Menghasilkan ID unik 8 karakter alfanumerik.
 * @return string
 */
function generateUniqueLabelId(): string {
    return strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
}