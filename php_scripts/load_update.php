<?php
/**
 * Update Sterilization Load Script (for AJAX Modal - Atomic & Simplified)
 *
 * This version uses an atomic UPDATE and is simplified by removing session logic.
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

// Set header for JSON response
header('Content-Type: application/json');

// --- Authorization & CSRF Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Silakan login.']);
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF tidak valid.']);
    exit;
}
$loggedInUserId = $_SESSION['user_id'] ?? null;

// --- Form Data Processing ---
$response = ['success' => false];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);
    $machineId = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
    $destinationDepartmentId = filter_input(INPUT_POST, 'destination_department_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $notes = trim($_POST['notes'] ?? '');

    // Basic Validation - PERUBAHAN: Disederhanakan, tidak ada lagi session_id
    if (!$loadId || !$machineId) {
        $response['error'] = 'Data tidak lengkap. Mesin wajib diisi.';
        echo json_encode($response);
        exit;
    }

    $conn = connectToDatabase();
    if (!$conn) {
        http_response_code(500);
        $response['error'] = 'Koneksi database gagal.';
        echo json_encode($response);
        exit;
    }

    try {
        // PERUBAHAN: Menghilangkan session_id dari query UPDATE
        $sql = "UPDATE sterilization_loads 
                SET machine_id = ?, destination_department_id = ?, notes = ?
                WHERE load_id = ? AND status = 'persiapan'";
        
        if ($stmt = $conn->prepare($sql)) {
            $notesToUpdate = !empty($notes) ? $notes : null;
            // PERUBAHAN: Menyesuaikan bind_param
            $stmt->bind_param("iisi", $machineId, $destinationDepartmentId, $notesToUpdate, $loadId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Detail muatan berhasil diperbarui.';
                    log_activity('UPDATE_LOAD', $loggedInUserId, "Detail muatan (ID: $loadId) telah diperbarui via modal.", 'load', $loadId);
                } else {
                    $response['success'] = false;
                    $response['error'] = 'Gagal memperbarui. Muatan mungkin tidak lagi dalam status "Persiapan" atau data yang dimasukkan sama dengan data sebelumnya.';
                }
            } else {
                throw new Exception("Gagal mengeksekusi pembaruan: " . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception("Gagal mempersiapkan statement: " . $conn->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response['error'] = "Terjadi kesalahan: " . $e->getMessage();
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
} else {
    http_response_code(405);
    $response['error'] = 'Metode permintaan tidak valid.';
}

echo json_encode($response);
exit;