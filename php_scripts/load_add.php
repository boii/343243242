<?php
/**
 * Add New Sterilization Load Script
 *
 * This version is simplified by removing the session requirement.
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
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Silakan login.'];
    header("Location: ../manage_loads.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../manage_loads.php");
    exit;
}
$loggedInUserId = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $machineId = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
    $destinationDepartmentId = filter_input(INPUT_POST, 'destination_department_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]); // Allow NULL
    $notes = trim($_POST['notes'] ?? '');

    // PERUBAHAN: Validasi hanya untuk machineId
    if (!$machineId) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Mesin wajib dipilih.'];
        header("Location: ../manage_loads.php");
        exit;
    }
    
    $conn = connectToDatabase();
    if (!$conn) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
        header("Location: ../manage_loads.php");
        exit;
    }

    try {
        $datePart = date('dmy');
        
        $stmtSeq = $conn->prepare("SELECT COUNT(load_id) as daily_count FROM sterilization_loads WHERE DATE(created_at) = CURDATE()");
        $stmtSeq->execute();
        $dailyCount = (int) $stmtSeq->get_result()->fetch_assoc()['daily_count'];
        $nextSeq = $dailyCount + 1;
        $stmtSeq->close();

        $loadName = sprintf("MUATAN-%s-%02d", $datePart, $nextSeq);
        
        // PERUBAHAN: Menghilangkan session_id dari query
        $sql = "INSERT INTO sterilization_loads (load_name, created_by_user_id, machine_id, destination_department_id, notes, status) VALUES (?, ?, ?, ?, ?, 'persiapan')";
        
        if ($stmt = $conn->prepare($sql)) {
            $notesToInsert = !empty($notes) ? $notes : null;
            // PERUBAHAN: Menyesuaikan bind_param
            $stmt->bind_param("siiis", $loadName, $loggedInUserId, $machineId, $destinationDepartmentId, $notesToInsert);
            
            if ($stmt->execute()) {
                $newLoadId = $stmt->insert_id;
                
                $successMessage = "Muatan baru '" . htmlspecialchars($loadName) . "' berhasil dibuat. Silakan tambahkan item.";
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
                
                log_activity('CREATE_LOAD', $loggedInUserId, "Muatan baru dibuat: " . $loadName, 'load', $newLoadId);

                header("Location: ../load_detail.php?load_id=" . $newLoadId);
                exit;
            } else {
                throw new Exception("Gagal membuat muatan baru: " . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan: " . $e->getMessage()];
    } finally {
        $conn->close();
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
}

header("Location: ../manage_loads.php");
exit;