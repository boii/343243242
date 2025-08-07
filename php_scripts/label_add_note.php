<?php
/**
 * Add Internal Note to Label Script
 *
 * Handles POST requests from verify_label.php to add an internal note
 * to a specific sterilization record. This action is restricted to
 * admins and supervisors.
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

// Authorization check: Only Admins and Supervisors can add internal notes
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
$newNote = trim($_POST['note'] ?? '');
$labelUniqueId = trim($_POST['label_unique_id'] ?? '');
$loggedInUserId = $_SESSION['user_id'] ?? null;
$loggedInUserName = $_SESSION['full_name'] ?? $_SESSION['username'];

if (!$recordId || empty($newNote) || empty($labelUniqueId)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Data tidak lengkap. Catatan tidak boleh kosong.'];
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

    // Get current notes to prepend the new one
    $stmtGet = $conn->prepare("SELECT notes FROM sterilization_records WHERE record_id = ? FOR UPDATE");
    $stmtGet->bind_param("i", $recordId);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    
    if (!($label = $result->fetch_assoc())) {
        throw new Exception("Label tidak ditemukan.");
    }
    $existingNotes = $label['notes'];
    $stmtGet->close();

    // Format the new note entry
    $notePrefix = "CATATAN INTERNAL (" . date('d-m-Y H:i') . " oleh " . htmlspecialchars($loggedInUserName) . "): ";
    $fullNewNote = $notePrefix . htmlspecialchars($newNote);

    // Prepend the new note to existing notes
    $updatedNotes = $fullNewNote . "\n-----------------\n" . $existingNotes;

    // Update the record
    $stmtUpdate = $conn->prepare("UPDATE sterilization_records SET notes = ? WHERE record_id = ?");
    $stmtUpdate->bind_param("si", $updatedNotes, $recordId);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception("Gagal menyimpan catatan: " . $stmtUpdate->error);
    }

    log_activity('ADD_INTERNAL_NOTE', $loggedInUserId, "Catatan internal ditambahkan ke Label UID: " . $labelUniqueId, 'label', $recordId);
    $conn->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Catatan internal berhasil ditambahkan.'];

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
} finally {
    if(isset($stmtUpdate)) $stmtUpdate->close();
    $conn->close();
}

header("Location: ../verify_label.php?uid=" . urlencode($labelUniqueId));
exit;