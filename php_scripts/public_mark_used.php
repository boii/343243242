<?php
/**
 * Mark Label as Used from Public Page (with Nerd Details Logging, User Note & Image Upload)
 *
 * This version captures a user-provided note and an optional proof image,
 * along with device/IP details and saves them for enhanced auditing.
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

// Data sekarang datang dari $_POST karena menggunakan FormData
$labelUid = trim($_POST['label_uid'] ?? '');
$userNote = trim($_POST['note'] ?? '');
$imageFile = $_FILES['usage_proof_image'] ?? null;
$newImageFilename = null;

if (empty($labelUid)) {
    $response['message'] = 'ID Label tidak boleh kosong.';
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
    // --- Logika Upload Gambar ---
    if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/usage_proof/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Gagal membuat direktori penyimpanan bukti.");
            }
        }

        $maxFileSize = 2 * 1024 * 1024; // 2 MB
        if ($imageFile['size'] > $maxFileSize) {
            throw new Exception("Ukuran file gambar terlalu besar. Maksimal 2MB.");
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileMimeType = mime_content_type($imageFile['tmp_name']);
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            throw new Exception("Format file tidak diizinkan. Harap unggah JPG, PNG, atau WebP.");
        }

        $fileExtension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
        $newImageFilename = 'proof_' . $labelUid . '_' . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newImageFilename;

        if (!move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
            throw new Exception("Gagal menyimpan file gambar yang diunggah.");
        }
    }

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
    $existingNotes = $label['notes'];

    if ($label['status'] !== 'active') {
        throw new Exception("Item ini sudah tidak aktif dan tidak bisa diubah statusnya.");
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

    $publicNote = "USED: " . date('d-m-Y H:i');
    if (!empty($userNote)) {
        $publicNote .= " - Oleh: " . htmlspecialchars($userNote);
    }

    $updatedNotes = $publicNote . "\n-----------------\n" . $existingNotes;

    // UPDATE query untuk menyertakan kolom baru `usage_proof_filename`
    $sqlUpdate = "UPDATE sterilization_records
                  SET status = 'used', used_at = NOW(), notes = ?,
                      action_ip_address = ?, action_user_agent = ?, usage_proof_filename = ?
                  WHERE record_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    // Tambahkan $newImageFilename ke bind_param
    $stmtUpdate->bind_param("ssssi", $updatedNotes, $ipAddress, $userAgent, $newImageFilename, $recordId);

    if (!$stmtUpdate->execute()) {
        throw new Exception("Gagal memperbarui status item.");
    }

    $logDetails = "Label (UID: " . htmlspecialchars($labelUid) . ") ditandai 'Digunakan' via aksi publik.";
    if(!empty($userNote)) {
        $logDetails .= " Catatan: " . htmlspecialchars($userNote);
    }
    if($newImageFilename) {
        $logDetails .= " Dengan bukti foto: " . $newImageFilename;
    }
    log_activity('PUBLIC_MARK_USED', null, $logDetails, 'label', $recordId);

    $conn->commit();
    $response = ['success' => true, 'message' => 'Status item berhasil diperbarui menjadi "Telah Digunakan"!'];

} catch (Exception $e) {
    if ($conn->inTransaction) $conn->rollback();
    // Hapus file yang sudah terunggah jika terjadi error database
    if ($newImageFilename && file_exists('../uploads/usage_proof/' . $newImageFilename)) {
        unlink('../uploads/usage_proof/' . $newImageFilename);
    }
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmtGet)) $stmtGet->close();
    if (isset($stmtUpdate)) $stmtUpdate->close();
    $conn->close();
}

echo json_encode($response);
exit;