<?php
/**
 * Public Script to Mark a Used Item as Accepted - Enriched Timeline UI/UX Revamp v13
 *
 * Handles the final acceptance of a used item, completing its lifecycle.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Backend
 * @package  Sterilabel
 * @author   UI/UX Specialist
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once '../config.php';

header('Content-Type: application/json');

// Get data from POST request
$labelUid = $_POST['label_uid'] ?? '';
$note = trim($_POST['note'] ?? '');
$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];

if (empty($labelUid)) {
    echo json_encode(['success' => false, 'message' => 'ID Label tidak valid.']);
    exit;
}

$conn = connectToDatabase();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Verify the current status of the label is 'used'
    $stmt = $conn->prepare("SELECT status, notes FROM sterilization_records WHERE label_unique_id = ? FOR UPDATE");
    $stmt->bind_param("s", $labelUid);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        throw new Exception('Label tidak ditemukan.');
    }

    if ($record['status'] !== 'used') {
        throw new Exception('Aksi ini hanya bisa dilakukan pada item yang sudah digunakan.');
    }

    // 2. Update status to 'used_accepted' and append notes
    $newStatus = 'used_accepted';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "-----------------\n[PENERIMAAN BARANG] pada {$timestamp} oleh {$ipAddress}\nUser Agent: {$userAgent}\n";
    if (!empty($note)) {
        $logMessage .= "Catatan: " . htmlspecialchars($note) . "\n";
    }
    $newNotes = $record['notes'] . $logMessage;

    $updateStmt = $conn->prepare("UPDATE sterilization_records SET status = ?, notes = ? WHERE label_unique_id = ?");
    $updateStmt->bind_param("sss", $newStatus, $newNotes, $labelUid);

    if (!$updateStmt->execute()) {
        throw new Exception('Gagal memperbarui status label.');
    }
    $updateStmt->close();
    
    // (Optional) Add to a dedicated activity log if exists
    // logActivity(null, "ITEM_ACCEPTED", "Barang dengan ID Label {$labelUid} telah diterima.", $ipAddress);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Barang berhasil diterima dan siklus selesai. Halaman akan dimuat ulang.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conn) {
        $conn->close();
    }
}