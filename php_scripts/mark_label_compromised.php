<?php
/**
 * Mark Label as Recalled/Compromised from Public Page (with Nerd Details Logging & Image Upload)
 *
 * This version handles an optional proof image upload, separates the public-facing
 * reason from internal details, and saves all data for enhanced auditing.
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
$reason = trim($_POST['reason'] ?? '');
$imageFile = $_FILES['issue_proof_image'] ?? null;
$newImageFilename = null;


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
    // --- Logika Upload Gambar ---
    if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/issue_proof/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Gagal membuat direktori penyimpanan bukti masalah.");
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
        // Membuat nama file yang unik untuk bukti masalah
        $newImageFilename = 'issue_' . $labelUid . '_' . time() . '.' . $fileExtension;
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
    $currentStatus = $label['status'];
    $existingNotes = $label['notes'];

    if ($currentStatus !== 'active') {
        throw new Exception("Item ini sudah tidak aktif dan tidak dapat dilaporkan.");
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';

    $publicNote = "RECALLED (public): " . date('d-m-Y H:i') . " - " . htmlspecialchars($reason);

    $updatedNotes = $publicNote . "\n-----------------\n" . $existingNotes;

    // UPDATE query untuk menyertakan kolom baru issue_proof_filename
    $sqlUpdate = "UPDATE sterilization_records
                  SET status = 'recalled', notes = ?, used_at = NULL,
                      action_ip_address = ?, action_user_agent = ?, issue_proof_filename = ?
                  WHERE record_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssssi", $updatedNotes, $ipAddress, $userAgent, $newImageFilename, $recordId);

    if (!$stmtUpdate->execute()) {
        throw new Exception("Gagal memperbarui status item.");
    }

    $logDetails = "Label (UID: " . htmlspecialchars($labelUid) . ") ditandai 'Recalled' via aksi publik. Alasan: " . htmlspecialchars($reason);
    if ($newImageFilename) {
        $logDetails .= " Dengan bukti foto: " . $newImageFilename;
    }
    log_activity('PUBLIC_RECALL_LABEL', null, $logDetails, 'label', $recordId);

    $conn->commit();
    $response = ['success' => true, 'message' => 'Masalah berhasil dilaporkan! Status item telah diperbarui menjadi "Ditarik Kembali".'];

} catch (Exception $e) {
    if ($conn->inTransaction) $conn->rollback();
    // Hapus file yang sudah terunggah jika terjadi error database
    if ($newImageFilename && file_exists('../uploads/issue_proof/' . $newImageFilename)) {
        unlink('../uploads/issue_proof/' . $newImageFilename);
    }
    $response['message'] = $e->getMessage();
    http_response_code(422); // Unprocessable Entity
} finally {
    if (isset($stmtGet)) $stmtGet->close();
    if (isset($stmtUpdate)) $stmtUpdate->close();
    $conn->close();
}

echo json_encode($response);
exit;