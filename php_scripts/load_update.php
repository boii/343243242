<?php
/**
 * Update Sterilization Load Script
 *
 * This version is updated to handle changes in machine, destination, notes, and
 * the new packaging_type_id. It ensures that the load status can only be
 * changed when the load is in the 'persiapan' (preparation) phase.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token CSRF tidak valid.']);
    exit;
}

header('Content-Type: application/json');

$loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);
$machineId = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
$destinationDepartmentId = filter_input(INPUT_POST, 'destination_department_id', FILTER_VALIDATE_INT);
$packagingTypeId = filter_input(INPUT_POST, 'packaging_type_id', FILTER_VALIDATE_INT);
$notes = trim($_POST['notes'] ?? '');

if (!$loadId || !$machineId) {
    echo json_encode(['success' => false, 'error' => 'Data yang dibutuhkan tidak lengkap.']);
    exit;
}

$conn = connectToDatabase();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Koneksi database gagal.']);
    exit;
}

try {
    // Check if load is in preparation status
    $sqlCheckStatus = "SELECT status FROM sterilization_loads WHERE load_id = ?";
    $stmtCheckStatus = $conn->prepare($sqlCheckStatus);
    $stmtCheckStatus->bind_param("i", $loadId);
    $stmtCheckStatus->execute();
    $resultStatus = $stmtCheckStatus->get_result();
    $load = $resultStatus->fetch_assoc();
    $stmtCheckStatus->close();

    if (!$load || $load['status'] !== 'persiapan') {
        echo json_encode(['success' => false, 'error' => 'Muatan hanya dapat diubah saat dalam status persiapan.']);
        $conn->close();
        exit;
    }

    $notesToInsert = !empty($notes) ? $notes : null;
    $destinationDeptToInsert = $destinationDepartmentId !== false ? $destinationDepartmentId : null;
    $packagingTypeToInsert = $packagingTypeId !== false ? $packagingTypeId : null;

    // PERUBAHAN: Menambahkan kolom `packaging_type_id`
    $sql = "UPDATE sterilization_loads SET machine_id = ?, destination_department_id = ?, packaging_type_id = ?, notes = ? WHERE load_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // PERUBAHAN: Menyesuaikan bind_param
        $stmt->bind_param("iiisi", $machineId, $destinationDeptToInsert, $packagingTypeToInsert, $notesToInsert, $loadId);

        if ($stmt->execute()) {
            log_activity('UPDATE_LOAD', $_SESSION['user_id'] ?? null, "Detail muatan (ID: {$loadId}) telah diperbarui via modal.", 'load', $loadId);
            echo json_encode(['success' => true, 'message' => 'Detail muatan berhasil diperbarui.']);
        } else {
            throw new Exception("Gagal memperbarui muatan: " . $stmt->error);
        }
        $stmt->close();
    } else {
        throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}