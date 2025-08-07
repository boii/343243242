<?php
/**
 * Update Load Item Snapshot (AJAX)
 *
 * Receives a load_item_id and a JSON string representing the modified
 * contents of a set, then updates the 'item_snapshot' for that specific
 * item within the load. This allows for on-the-fly modifications without
 * altering the master set template.
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

header('Content-Type: application/json');

// --- Authorization & CSRF Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metode permintaan tidak valid.']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$csrfToken = $input['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF tidak valid.']);
    exit;
}

// --- Data Validation ---
$loadItemId = filter_var($input['load_item_id'] ?? null, FILTER_VALIDATE_INT);
$snapshotData = $input['snapshot'] ?? null;

if (!$loadItemId || !is_array($snapshotData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Data yang dikirim tidak lengkap atau tidak valid.']);
    exit;
}

$snapshotJson = json_encode($snapshotData);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Data snapshot tidak valid.']);
    exit;
}

$response = ['success' => false];
$conn = connectToDatabase();
if (!$conn) {
    http_response_code(500);
    $response['error'] = 'Koneksi database gagal.';
    echo json_encode($response);
    exit;
}

try {
    // Verifikasi bahwa item ini milik muatan yang masih dalam status 'persiapan'
    $sqlCheck = "SELECT sl.status 
                 FROM sterilization_load_items sli
                 JOIN sterilization_loads sl ON sli.load_id = sl.load_id
                 WHERE sli.load_item_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $loadItemId);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['status'] !== 'persiapan') {
            throw new Exception("Tidak dapat mengubah isi set karena muatan tidak lagi dalam status 'Persiapan'.");
        }
    } else {
        throw new Exception("Item muatan tidak ditemukan.");
    }
    $stmtCheck->close();

    // Lanjutkan dengan pembaruan
    $stmtUpdate = $conn->prepare("UPDATE sterilization_load_items SET item_snapshot = ? WHERE load_item_id = ?");
    $stmtUpdate->bind_param("si", $snapshotJson, $loadItemId);

    if ($stmtUpdate->execute()) {
        $response['success'] = true;
        $response['message'] = 'Isi set untuk muatan ini berhasil diperbarui.';
        log_activity(
            'UPDATE_LOAD_ITEM_SNAPSHOT',
            $_SESSION['user_id'],
            "Snapshot untuk load_item_id: {$loadItemId} diperbarui.",
            'load_item',
            $loadItemId
        );
    } else {
        throw new Exception("Eksekusi pembaruan gagal: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
} finally {
    $conn->close();
}

echo json_encode($response);