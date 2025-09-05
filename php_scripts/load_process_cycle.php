<?php
/**
 * Process a Sterilization Load and Cycle (Direct to Complete - Simplified)
 *
 * This version is corrected to remove the 'sterilization_method' column from the INSERT query,
 * aligning it with the simplified database schema.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   UI/UX Specialist
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

$loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);
$processType = trim($_POST['process_type'] ?? '');
$targetCycleId = filter_input(INPUT_POST, 'target_cycle_id', FILTER_VALIDATE_INT);
// PERUBAHAN: Variabel sterilization_method dihapus karena tidak lagi digunakan.

if (!$loadId || empty($processType)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Data tidak lengkap untuk memproses muatan.'];
    header("Location: ../manage_loads.php");
    exit;
}
if ($processType === 'merge_existing' && !$targetCycleId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda harus memilih siklus target untuk digabungkan.'];
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
    // Get the load details, ensuring it's in 'persiapan' status
    $stmtGetLoad = $conn->prepare("SELECT load_name, machine_id, created_by_user_id FROM sterilization_loads WHERE load_id = ? AND status = 'persiapan' FOR UPDATE");
    $stmtGetLoad->bind_param("i", $loadId);
    $stmtGetLoad->execute();
    $resultLoad = $stmtGetLoad->get_result();
    if (!($load = $resultLoad->fetch_assoc())) {
        throw new Exception("Muatan tidak ditemukan atau statusnya bukan 'Persiapan'.");
    }
    $loadName = $load['load_name'];
    $machineId = $load['machine_id'];
    $loadCreatorId = $load['created_by_user_id'];
    $stmtGetLoad->close();

    if (!$machineId) {
        throw new Exception("Informasi mesin tidak ditemukan pada muatan ini.");
    }
    
    $finalCycleId = null;
    $finalCycleNumber = '';

    if ($processType === 'create_new') {
        // --- Logic to create a new cycle ---
        $stmtGetMachine = $conn->prepare("SELECT machine_name, machine_code FROM machines WHERE machine_id = ?");
        $stmtGetMachine->bind_param("i", $machineId);
        $stmtGetMachine->execute();
        $machine = $stmtGetMachine->get_result()->fetch_assoc();
        $machineName = $machine['machine_name'];
        $machineCode = $machine['machine_code'];
        $stmtGetMachine->close();

        $datePart = date('dmy');
        $likePattern = "SIKLUS-" . $machineCode . '-' . $datePart . '-%';
        $stmtGetSeq = $conn->prepare("SELECT COUNT(cycle_id) as daily_count FROM sterilization_cycles WHERE cycle_number LIKE ?");
        $stmtGetSeq->bind_param("s", $likePattern);
        $stmtGetSeq->execute();
        $dailyCount = (int) $stmtGetSeq->get_result()->fetch_assoc()['daily_count'];
        $nextSeq = $dailyCount + 1;
        $cycleNumber = sprintf("SIKLUS-%s-%s-%02d", $machineCode, $datePart, $nextSeq);
        $stmtGetSeq->close();
        $finalCycleNumber = $cycleNumber;

        // PERUBAHAN: Menghapus kolom 'sterilization_method' dari query INSERT
        $sqlCreateCycle = "INSERT INTO sterilization_cycles (machine_name, cycle_number, cycle_date, operator_user_id, status) VALUES (?, ?, NOW(), ?, 'completed')";
        $stmtCycle = $conn->prepare($sqlCreateCycle);
        // PERUBAHAN: Menyesuaikan bind_param, menghapus 's' untuk sterilization_method
        $stmtCycle->bind_param("ssi", $machineName, $cycleNumber, $loggedInUserId);
        if (!$stmtCycle->execute()) {
            throw new Exception("Gagal membuat data siklus baru: " . $stmtCycle->error);
        }
        $finalCycleId = $stmtCycle->insert_id;
        $stmtCycle->close();
        
        $successMessage = "Muatan berhasil diproses dengan siklus baru: " . htmlspecialchars($cycleNumber) . ". Silakan buat label.";
        log_activity('CREATE_CYCLE_FROM_LOAD', $loggedInUserId, "Siklus baru (ID: $finalCycleId, No: $cycleNumber) dibuat dan langsung diselesaikan untuk Muatan ID: $loadId.");

    } elseif ($processType === 'merge_existing') {
        // --- Logic to merge into an existing cycle ---
        $finalCycleId = $targetCycleId;

        $stmtCheckCycle = $conn->prepare("SELECT cycle_number FROM sterilization_cycles WHERE cycle_id = ? AND status = 'completed' AND DATE(cycle_date) = CURDATE()");
        $stmtCheckCycle->bind_param("i", $finalCycleId);
        $stmtCheckCycle->execute();
        $resultCheckCycle = $stmtCheckCycle->get_result();
        if($cycleToMerge = $resultCheckCycle->fetch_assoc()) {
            $finalCycleNumber = $cycleToMerge['cycle_number'];
        } else {
            throw new Exception("Siklus target tidak ditemukan atau tidak valid untuk digabungkan (harus berstatus 'Selesai' dan dibuat hari ini).");
        }
        $stmtCheckCycle->close();
        
        $successMessage = 'Muatan berhasil digabungkan ke siklus yang sudah ada. Silakan buat label.';
        log_activity('MERGE_LOAD_TO_CYCLE', $loggedInUserId, "Muatan ID: $loadId digabungkan ke Siklus ID: $finalCycleId.");
    }

    $sqlUpdateLoad = "UPDATE sterilization_loads SET cycle_id = ?, status = 'selesai' WHERE load_id = ?";
    $stmtLoad = $conn->prepare($sqlUpdateLoad);
    $stmtLoad->bind_param("ii", $finalCycleId, $loadId);
    if (!$stmtLoad->execute()) {
        throw new Exception("Gagal memperbarui status muatan: " . $stmtLoad->error);
    }
    $stmtLoad->close();

    if ($loadCreatorId && $loadCreatorId != $loggedInUserId) {
        $notifTitle = "Muatan Telah Diproses";
        $notifMessage = "Muatan '{$loadName}' telah selesai diproses dalam siklus '{$finalCycleNumber}' dan siap untuk dibuatkan label.";
        $notifLink = "load_detail.php?load_id=" . $loadId;
        $notifIcon = "task_alt";

        $sqlNotif = "INSERT INTO user_notifications (user_id, icon, title, message, link) VALUES (?, ?, ?, ?, ?)";
        if ($stmtNotif = $conn->prepare($sqlNotif)) {
            $stmtNotif->bind_param("issss", $loadCreatorId, $notifIcon, $notifTitle, $notifMessage, $notifLink);
            $stmtNotif->execute();
            $stmtNotif->close();
        }
    }

    $conn->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan: " . $e->getMessage()];
} finally {
    if ($conn) {
        $conn->close();
    }
}

header("Location: ../load_detail.php?load_id=" . $loadId);
exit;