<?php
/**
 * Delete Instrument Script (with Image File Cleanup)
 *
 * This version enhances the deletion process by also removing the associated
 * image file from the server to prevent orphaned files. It also grants
 * access to supervisors.
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

// --- Authorization Check for Admins, Supervisors, and authorized Staff ---
$userRole = $_SESSION['role'] ?? null;
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');

if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !(in_array($userRole, ['admin', 'supervisor']) || ($userRole === 'staff' && $staffCanManageInstruments))
) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda tidak memiliki izin untuk menghapus instrumen.'];
    header("Location: ../manage_instruments.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid atau token CSRF salah. Silakan coba lagi.'];
    header("Location: ../manage_instruments.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $instrumentIdToDelete = filter_input(INPUT_POST, 'instrument_id_to_delete', FILTER_VALIDATE_INT);

    if (!$instrumentIdToDelete) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID instrumen untuk dihapus tidak valid.'];
        header("Location: ../manage_instruments.php");
        exit;
    }

    $conn = connectToDatabase();
    if (!$conn) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
        header("Location: ../manage_instruments.php");
        exit;
    }

    try {
        // --- PERUBAHAN BARU: Ambil nama instrumen & nama file gambar sebelum menghapus ---
        $instrumentNameToDelete = 'ID: ' . $instrumentIdToDelete;
        $imageFilenameToDelete = null;
        $stmtGet = $conn->prepare("SELECT instrument_name, image_filename FROM instruments WHERE instrument_id = ?");
        $stmtGet->bind_param("i", $instrumentIdToDelete);
        $stmtGet->execute();
        if ($row = $stmtGet->get_result()->fetch_assoc()) {
            $instrumentNameToDelete = $row['instrument_name'];
            $imageFilenameToDelete = $row['image_filename'];
        }
        $stmtGet->close();

        // Dependency Check 1: Is instrument in any sets?
        $stmtCheckSets = $conn->prepare("SELECT set_item_id FROM instrument_set_items WHERE instrument_id = ? LIMIT 1");
        $stmtCheckSets->bind_param("i", $instrumentIdToDelete);
        $stmtCheckSets->execute();
        if ($stmtCheckSets->get_result()->num_rows > 0) {
            throw new Exception("Instrumen '" . htmlspecialchars($instrumentNameToDelete) . "' tidak dapat dihapus karena merupakan bagian dari satu atau lebih set instrumen.");
        }
        $stmtCheckSets->close();

        // Dependency Check 2: Is instrument used in ANY sterilization label (as an individual item)?
        $stmtCheckLabels = $conn->prepare("SELECT record_id FROM sterilization_records WHERE item_id = ? AND item_type = 'instrument' LIMIT 1");
        $stmtCheckLabels->bind_param("i", $instrumentIdToDelete);
        $stmtCheckLabels->execute();
        if ($stmtCheckLabels->get_result()->num_rows > 0) {
            throw new Exception("Instrumen '" . htmlspecialchars($instrumentNameToDelete) . "' tidak dapat dihapus karena memiliki riwayat pelabelan. Sebaiknya ubah statusnya menjadi 'Rusak' atau 'Diarsipkan'.");
        }
        $stmtCheckLabels->close();

        // Proceed with deletion
        $sqlDelete = "DELETE FROM instruments WHERE instrument_id = ?";
        $stmtDelete = $conn->prepare($sqlDelete);
        $stmtDelete->bind_param("i", $instrumentIdToDelete);

        if ($stmtDelete->execute()) {
            if ($stmtDelete->affected_rows > 0) {
                // --- PERUBAHAN BARU: Hapus file gambar jika ada ---
                if (!empty($imageFilenameToDelete)) {
                    $filePath = '../uploads/instruments/' . $imageFilenameToDelete;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Instrumen '" . htmlspecialchars($instrumentNameToDelete) . "' berhasil dihapus."];
                log_activity('DELETE_INSTRUMENT', $loggedInUserId, "Instrumen dihapus: '" . $instrumentNameToDelete . "' (ID: " . $instrumentIdToDelete . ").", 'instrument', $instrumentIdToDelete);
            } else {
                $_SESSION['flash_message'] = ['type' => 'info', 'text' => "Instrumen dengan ID " . $instrumentIdToDelete . " tidak ditemukan."];
            }
        } else {
            throw new Exception("Gagal menghapus instrumen: " . $stmtDelete->error);
        }
        $stmtDelete->close();

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    } finally {
        $conn->close();
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
}

header("Location: ../manage_instruments.php");
exit;