<?php
/**
 * Void an Active Label
 *
 * Handles POST requests from verify_label.php to change a label's status
 * to 'voided'. This action is restricted to admins and supervisors and requires a reason.
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

// Authorization check: Only Admins and Supervisors can void a label
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../index.php");
    exit;
}

// CSRF Token Check
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    $redirectUid = $_POST['label_unique_id'] ?? '';
    header("Location: ../verify_label.php?uid=" . urlencode($redirectUid));
    exit;
}

$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
$reason = trim($_POST['reason'] ?? '');
$labelUniqueId = trim($_POST['label_unique_id'] ?? '');
$loggedInUserId = $_SESSION['user_id'] ?? null;
$loggedInUserName = $_SESSION['full_name'] ?? $_SESSION['username'];

if (!$recordId || empty($reason) || empty($labelUniqueId)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Data tidak lengkap. Alasan pembatalan wajib diisi.'];
    header("Location: ../verify_label.php?uid=" . urlencode($labelUniqueId));
    exit;
}

$conn = connectToDatabase();
if (!$conn) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
    header("Location: ../verify_label.php?uid=" . urlencode($labelUniqueId));
    exit;
}

try {
    $conn->begin_transaction();

    $stmtGet = $conn->prepare("SELECT notes, status FROM sterilization_records WHERE record_id = ? FOR UPDATE");
    $stmtGet->bind_param("i", $recordId);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    
    if (!($label = $result->fetch_assoc())) {
        throw new Exception("Label tidak ditemukan.");
    }
    
    if ($label['status'] !== 'active') {
        throw new Exception("Hanya label berstatus 'Aktif' yang bisa dibatalkan.");
    }

    $existingNotes = $label['notes'];
    $stmtGet->close();

    $notePrefix = "LABEL DIBATALKAN (" . date('d-m-Y H:i') . " oleh " . htmlspecialchars($loggedInUserName) . "): ";
    $fullNewNote = $notePrefix . htmlspecialchars($reason);

    $updatedNotes = $fullNewNote . "\n-----------------\n" . $existingNotes;

    $stmtUpdate = $conn->prepare("UPDATE sterilization_records SET status = 'voided', notes = ? WHERE record_id = ?");
    $stmtUpdate->bind_param("si", $updatedNotes, $recordId);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception("Gagal membatalkan label: " . $stmtUpdate->error);
    }

    log_activity('VOID_LABEL', $loggedInUserId, "Label UID: " . $labelUniqueId . " dibatalkan. Alasan: " . htmlspecialchars($reason), 'label', $recordId);
    $conn->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Label telah berhasil dibatalkan.'];

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
} finally {
    if(isset($stmtUpdate)) $stmtUpdate->close();
    $conn->close();
}

header("Location: ../verify_label.php?uid=" . urlencode($labelUniqueId));
exit;