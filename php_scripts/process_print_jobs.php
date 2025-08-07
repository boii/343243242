<?php
/**
 * Process Print Jobs Script (AJAX)
 *
 * Marks labels as 'printed' and removes them from the print queue.
 * Called asynchronously from print_queue.php after a print job is sent.
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

// Authorization & CSRF Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses tidak sah.']);
    exit;
}

$recordIds = $_POST['record_ids'] ?? [];
$loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);

if (empty($recordIds) || !is_array($recordIds) || !$loadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tidak ada ID label yang valid untuk diproses.']);
    exit;
}

$sanitizedRecordIds = array_map('intval', $recordIds);
$placeholders = implode(',', array_fill(0, count($sanitizedRecordIds), '?'));
$types = str_repeat('i', count($sanitizedRecordIds));

$response = ['success' => false];
$conn = connectToDatabase();
if ($conn) {
    $conn->begin_transaction();
    try {
        // 1. Update print_status in sterilization_records table
        $stmtUpdate = $conn->prepare("UPDATE sterilization_records SET print_status = 'printed' WHERE record_id IN ($placeholders)");
        $stmtUpdate->bind_param($types, ...$sanitizedRecordIds);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // 2. Remove from print_queue table
        $stmtDelete = $conn->prepare("DELETE FROM print_queue WHERE record_id IN ($placeholders)");
        $stmtDelete->bind_param($types, ...$sanitizedRecordIds);
        $stmtDelete->execute();
        $stmtDelete->close();
        
        $conn->commit();
        $response['success'] = true;

    } catch (Exception $e) {
        $conn->rollback();
        $response['error'] = 'Gagal memproses antrean cetak: ' . $e->getMessage();
        http_response_code(500);
    } finally {
        $conn->close();
    }
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
?>