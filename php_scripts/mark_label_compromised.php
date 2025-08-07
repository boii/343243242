<?php
/**
 * Mark Label as Recalled/Compromised from Public Page (with Nerd Details Logging)
 *
 * This version separates the public-facing reason from the internal-only
 * device and IP address details for enhanced auditing and security.
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

$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$labelUid = trim($input['label_uid'] ?? '');
$reason = trim($input['reason'] ?? '');

if (empty($labelUid)) {
    $response['message'] = 'ID Label tidak boleh kosong.';
    echo json_encode($response);
    exit;
}
if (empty($reason)) {
    $response['message'] = 'Alasan pelaporan masalah wajib diisi.';
    echo json_encode($response);
    exit;
}

$conn = connectToDatabase();
if (!$conn) {
    $response['message'] = 'Kesalahan koneksi database.';
    http_response_code(500); 
    echo json_encode($response);
    exit;
}

try {
    $conn->begin_transaction();

    $sqlGetLabel = "SELECT record_id, status, notes FROM sterilization_records WHERE label_unique_id = ? LIMIT 1 FOR UPDATE";
    $stmtGet = $conn->prepare($sqlGetLabel);
    $stmtGet->bind_param("s", $labelUid);
    $stmtGet->execute();
    $result = $stmtGet->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Label tidak ditemukan.");
    }

    $label = $result->fetch_assoc();
    $recordId = $label['record_id'];
    $currentStatus = $label['status'];
    $existingNotes = $label['notes'];

    if ($currentStatus !== 'active') {
        throw new Exception("Item ini sudah tidak aktif dan tidak dapat dilaporkan.");
    }
    
    // --- Menangkap Detail Teknis ---
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
    
    // --- Memformat catatan baru (hanya catatan publik) ---
    $publicNote = "RECALLED (public): " . date('d-m-Y H:i') . " - " . htmlspecialchars($reason);
    
    // Gabungkan hanya catatan publik dengan yang sudah ada
    $updatedNotes = $publicNote . "\n-----------------\n" . $existingNotes;
    
    // --- PERUBAHAN: UPDATE query untuk menyertakan kolom baru ---
    $sqlUpdate = "UPDATE sterilization_records SET status = 'recalled', notes = ?, used_at = NULL, action_ip_address = ?, action_user_agent = ? WHERE record_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sssi", $updatedNotes, $ipAddress, $userAgent, $recordId);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception("Gagal memperbarui status item.");
    }
    
    $logDetails = "Label (UID: " . htmlspecialchars($labelUid) . ") ditandai 'Recalled' via aksi publik. Alasan: " . htmlspecialchars($reason);
    log_activity('PUBLIC_RECALL_LABEL', null, $logDetails, 'label', $recordId);
    
    $conn->commit();
    $response = ['success' => true, 'message' => 'Status item berhasil diperbarui menjadi "Ditarik Kembali"!'];

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
    http_response_code(422);
} finally {
    if (isset($stmtGet)) $stmtGet->close();
    if (isset($stmtUpdate)) $stmtUpdate->close();
    $conn->close();
}

echo json_encode($response);
exit;