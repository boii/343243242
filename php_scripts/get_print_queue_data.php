<?php
/**
 * Get Print Queue Data (AJAX Endpoint)
 *
 * Fetches all unprinted labels for a specific load from the print_queue.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

$loadId = filter_input(INPUT_GET, 'load_id', FILTER_VALIDATE_INT);
if (!$loadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID Muatan tidak valid.']);
    exit;
}

$response = ['success' => false, 'labels' => []];
$conn = connectToDatabase();

if ($conn) {
    // Fetch records from the print queue for the specified load
    $sql = "SELECT 
                sr.record_id,
                sr.label_unique_id,
                sr.label_title
            FROM print_queue pq
            JOIN sterilization_records sr ON pq.record_id = sr.record_id
            WHERE pq.load_id = ? 
            ORDER BY sr.label_title ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $loadId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $response['labels'][] = $row;
        }
        $stmt->close();
        $response['success'] = true;
    } else {
        $response['error'] = 'Gagal mempersiapkan statement untuk mengambil antrean cetak.';
    }
    $conn->close();
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
?>