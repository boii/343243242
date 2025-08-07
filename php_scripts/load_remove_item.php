<?php
/**
 * Remove Item from Sterilization Load Script (AJAX - Atomic)
 *
 * This version uses a single, atomic DELETE query with a JOIN to prevent
 * race conditions, ensuring an item is only removed if the load is
 * still in 'persiapan' status at the moment of deletion.
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
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF tidak valid.']);
    exit;
}

$response = ['success' => false];
$loadItemId = filter_input(INPUT_POST, 'load_item_id', FILTER_VALIDATE_INT);

if (!$loadItemId) {
    $response['error'] = 'ID Item Muatan tidak valid.';
    echo json_encode($response);
    exit;
}

$conn = connectToDatabase();
if (!$conn) {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

try {
    // --- PERUBAHAN DIMULAI: Menggunakan satu query atomik untuk keamanan ---
    $sqlDelete = "DELETE sli FROM sterilization_load_items AS sli
                  JOIN sterilization_loads AS sl ON sli.load_id = sl.load_id
                  WHERE sli.load_item_id = ? AND sl.status = 'persiapan'";
    
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $loadItemId);
    
    if ($stmtDelete->execute()) {
        // Periksa apakah ada baris yang terpengaruh. Jika 0, berarti kondisi tidak terpenuhi.
        if ($stmtDelete->affected_rows > 0) {
            $response['success'] = true;
        } else {
            // Ini terjadi jika item tidak ditemukan ATAU status muatan BUKAN 'persiapan'.
            $response['error'] = 'Gagal menghapus item. Item mungkin tidak ditemukan atau status muatan bukan lagi "Persiapan".';
        }
    } else {
        throw new Exception('Eksekusi query penghapusan gagal: ' . $stmtDelete->error);
    }
    $stmtDelete->close();
    // --- PERUBAHAN SELESAI ---

} catch (Exception $e) {
    $response['error'] = 'Terjadi kesalahan server: ' . $e->getMessage();
    http_response_code(500);
} finally {
    $conn->close();
}

echo json_encode($response);
exit;