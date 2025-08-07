<?php
/**
 * Update Instrument Status Script (Supervisor Access Enabled)
 *
 * This version updates the authorization logic to grant access
 * to users with the 'supervisor' role.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   Gemini
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once '../config.php';

// --- PERUBAHAN: Menambahkan 'supervisor' ke dalam logika hak akses ---
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    !($_SESSION["role"] === 'admin' || $_SESSION["role"] === 'supervisor' || ($staffCanManageInstruments && $_SESSION["role"] === 'staff'))
) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda tidak memiliki izin untuk mengubah status instrumen.'];
    $redirectUrl = isset($_POST['instrument_id']) ? "../instrument_detail.php?instrument_id=" . urlencode($_POST['instrument_id']) : "../manage_instruments.php";
    header("Location: " . $redirectUrl);
    exit;
}
// --- AKHIR PERUBAHAN ---

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    $redirectUrl = isset($_POST['instrument_id']) ? "../instrument_detail.php?instrument_id=" . urlencode($_POST['instrument_id']) : "../manage_instruments.php";
    header("Location: " . $redirectUrl);
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;

$errorMessages = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $instrumentId = filter_input(INPUT_POST, 'instrument_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['new_status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // --- Validation ---
    $allowedStatuses = ['tersedia', 'perbaikan', 'rusak', 'sterilisasi'];
    if (!$instrumentId) {
        $errorMessages[] = "ID Instrumen tidak valid.";
    }
    if (empty($newStatus) || !in_array($newStatus, $allowedStatuses)) {
        $errorMessages[] = "Status baru yang dipilih tidak valid.";
    }
    if ($newStatus === 'rusak' && empty($notes)) {
        $errorMessages[] = "Catatan wajib diisi jika status diubah menjadi 'Rusak'.";
    }

    if (empty($errorMessages)) {
        $conn = connectToDatabase();
        if (!$conn) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            $conn->begin_transaction();
            try {
                // 1. Update status in instruments table
                $stmtUpdate = $conn->prepare("UPDATE instruments SET status = ? WHERE instrument_id = ?");
                $stmtUpdate->bind_param("si", $newStatus, $instrumentId);
                if (!$stmtUpdate->execute()) {
                    throw new Exception("Gagal memperbarui status instrumen: " . $stmtUpdate->error);
                }
                $stmtUpdate->close();

                // 2. Insert record into instrument_history table
                $stmtHistory = $conn->prepare("INSERT INTO instrument_history (instrument_id, changed_to_status, user_id, notes) VALUES (?, ?, ?, ?)");
                $notesToInsert = !empty($notes) ? $notes : null;
                $stmtHistory->bind_param("isis", $instrumentId, $newStatus, $loggedInUserId, $notesToInsert);
                if (!$stmtHistory->execute()) {
                    throw new Exception("Gagal mencatat riwayat status: " . $stmtHistory->error);
                }
                $stmtHistory->close();

                $conn->commit();
                
                $successMessage = "Status instrumen berhasil diperbarui menjadi '" . htmlspecialchars($newStatus) . "'.";
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
                
                // Logging
                $logDetails = "Status instrumen (ID: $instrumentId) diubah menjadi '" . htmlspecialchars($newStatus) . "'.";
                if ($notesToInsert) {
                    $logDetails .= " Catatan: " . $notesToInsert;
                }
                log_activity('UPDATE_INSTRUMENT_STATUS', $loggedInUserId, $logDetails, 'instrument', $instrumentId);

            } catch (Exception $e) {
                if ($conn->inTransaction) $conn->rollback();
                $errorMessages[] = "Terjadi kesalahan server: " . $e->getMessage();
            } finally {
                $conn->close();
            }
        }
    }
} else {
    $errorMessages[] = "Permintaan tidak valid.";
}

if (!empty($errorMessages)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
}

$redirectUrl = $instrumentId ? "../instrument_detail.php?instrument_id=" . $instrumentId : "../manage_instruments.php";
header("Location: " . $redirectUrl);
exit;