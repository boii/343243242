<?php
/**
 * Add Item to Sterilization Load Script (AJAX - with Snapshot)
 *
 * This version now creates a JSON snapshot of a set's contents
 * at the moment it's added to a load, storing it in the new
 * 'item_snapshot' column.
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

$loadId = filter_input(INPUT_POST, 'load_id', FILTER_VALIDATE_INT);
$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$itemType = trim($_POST['item_type'] ?? '');

if (!$loadId || !$itemId || !in_array($itemType, ['instrument', 'set'])) {
    $response['error'] = 'Data yang dikirim tidak lengkap atau tidak valid.';
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
    // Check if load is still in 'persiapan' status
    $stmtCheck = $conn->prepare("SELECT status FROM sterilization_loads WHERE load_id = ?");
    $stmtCheck->bind_param("i", $loadId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($load = $resultCheck->fetch_assoc()) {
        if ($load['status'] !== 'persiapan') {
            throw new Exception('Tidak dapat menambahkan item. Muatan tidak lagi dalam status persiapan.');
        }
    } else {
        throw new Exception('Muatan tidak ditemukan.');
    }
    $stmtCheck->close();

    // --- PERUBAHAN DIMULAI: Logika pembuatan snapshot ---
    $itemSnapshotJson = null;

    if ($itemType === 'set') {
        $snapshotItems = [];
        $stmtSnapshot = $conn->prepare(
            "SELECT instrument_id, quantity 
             FROM instrument_set_items 
             WHERE set_id = ?"
        );
        $stmtSnapshot->bind_param("i", $itemId);
        $stmtSnapshot->execute();
        $resultSnapshot = $stmtSnapshot->get_result();
        while ($item = $resultSnapshot->fetch_assoc()) {
            $snapshotItems[] = [
                'instrument_id' => (int)$item['instrument_id'],
                'quantity' => (int)$item['quantity']
            ];
        }
        $stmtSnapshot->close();
        $itemSnapshotJson = json_encode($snapshotItems);
    }
    
    // PERUBAHAN: Menambahkan kolom item_snapshot ke query INSERT
    $sqlInsert = "INSERT INTO sterilization_load_items (load_id, item_id, item_type, item_snapshot) VALUES (?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("iiss", $loadId, $itemId, $itemType, $itemSnapshotJson);
    
    if ($stmtInsert->execute()) {
        $response['success'] = true;
        $response['new_item_id'] = $stmtInsert->insert_id; // Kirim ID item yang baru dibuat untuk highlight
    } else {
        throw new Exception('Gagal menambahkan item: ' . $stmtInsert->error);
    }
    $stmtInsert->close();
    // --- PERUBAHAN SELESAI ---

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
} finally {
    $conn->close();
}

echo json_encode($response);