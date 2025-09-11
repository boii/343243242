<?php
/**
 * Public Script to Mark a Used Item as Accepted - Enriched Timeline UI/UX Revamp v13
 *
 * Handles the final acceptance of a used item, completing its lifecycle.
 * Records the return condition of the item and handles proof image upload if damaged.
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
$returnCondition = $_POST['return_condition'] ?? '';
$returnNotes = trim($_POST['return_notes'] ?? '');
$ipAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$returnProofImage = $_FILES['return_proof_image'] ?? null;
$newFileName = null;

// --- VALIDATION ---
if (empty($labelUid)) {
    echo json_encode(['success' => false, 'message' => 'ID Label tidak valid.']);
    exit;
}

if (empty($returnCondition) || !in_array($returnCondition, ['good', 'damaged'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan pilih kondisi barang.']);
    exit;
}

if ($returnCondition === 'damaged') {
    if (empty($returnNotes)) {
        echo json_encode(['success' => false, 'message' => 'Harap jelaskan masalah atau kerusakan pada barang.']);
        exit;
    }
    if (empty($returnProofImage) || $returnProofImage['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Harap unggah foto sebagai bukti kerusakan.']);
        exit;
    }
}

$conn = connectToDatabase();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

try {
    // --- FILE UPLOAD LOGIC (if damaged) ---
    if ($returnCondition === 'damaged' && $returnProofImage) {
        $uploadDir = '../uploads/return_proof/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($returnProofImage['type'], $allowedTypes)) {
            throw new Exception('Format file tidak valid. Hanya JPG, PNG, atau WEBP yang diizinkan.');
        }
        if ($returnProofImage['size'] > 2 * 1024 * 1024) { // 2 MB limit
            throw new Exception('Ukuran file terlalu besar. Maksimal 2 MB.');
        }

        $fileExtension = pathinfo($returnProofImage['name'], PATHINFO_EXTENSION);
        $newFileName = 'return_' . $labelUid . '_' . time() . '.' . $fileExtension;

        if (!move_uploaded_file($returnProofImage['tmp_name'], $uploadDir . $newFileName)) {
            throw new Exception('Gagal menyimpan file yang diunggah.');
        }
    }

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

    // 2. Prepare log message
    $newStatus = 'used_accepted';
    $timestamp = date('Y-m-d H:i:s');
    $conditionText = $returnCondition === 'good' ? 'Baik' : 'Ada Masalah';

    $logMessage = "-----------------\n[PENERIMAAN BARANG] pada {$timestamp} oleh {$ipAddress}\n";
    $logMessage .= "Kondisi Barang: {$conditionText}\n";

    if ($returnCondition === 'damaged') {
        $logMessage .= "Detail Masalah: " . htmlspecialchars($returnNotes) . "\n";
    }
    if (!empty($note)) {
        $logMessage .= "Catatan Tambahan: " . htmlspecialchars($note) . "\n";
    }
    $logMessage .= "User Agent: {$userAgent}\n";

    $newNotes = $record['notes'] . $logMessage;

    // 3. Update status, return condition, notes, and proof filename
    $updateStmt = $conn->prepare(
        "UPDATE sterilization_records
         SET status = ?, notes = ?, return_condition = ?, return_notes = ?, return_proof_filename = ?
         WHERE label_unique_id = ?"
    );

    $finalReturnNotes = ($returnCondition === 'good') ? null : $returnNotes;

    $updateStmt->bind_param("ssssss", $newStatus, $newNotes, $returnCondition, $finalReturnNotes, $newFileName, $labelUid);

    if (!$updateStmt->execute()) {
        throw new Exception('Gagal memperbarui status label.');
    }
    $updateStmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Barang berhasil diterima dan siklus selesai. Halaman akan dimuat ulang.']);

} catch (Exception $e) {
    $conn->rollback();
    // Jika file sudah terlanjur di-upload tapi DB gagal, hapus filenya
    if ($newFileName && file_exists($uploadDir . $newFileName)) {
        unlink($uploadDir . $newFileName);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if ($conn) {
        $conn->close();
    }
}
