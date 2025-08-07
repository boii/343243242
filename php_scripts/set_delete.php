<?php
/**
 * Delete Instrument Set Script (with Stricter Dependency Checks)
 *
 * Prevents deletion if the set has ANY label history in sterilization_records
 * to ensure data integrity. Supervisor access is now correctly granted.
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

// Authorization & CSRF Check
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');

// --- PERUBAHAN DIMULAI: Menambahkan 'supervisor' ke dalam logika hak akses ---
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !($_SESSION["role"] === 'admin' || $_SESSION["role"] === 'supervisor' || ($_SESSION["role"] === 'staff' && $staffCanManageSets))
) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.'];
    header("Location: ../manage_sets.php");
    exit;
}
// --- PERUBAHAN SELESAI ---

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid atau token CSRF salah. Silakan coba lagi.'];
    header("Location: ../manage_sets.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $setIdToDelete = filter_input(INPUT_POST, 'set_id_to_delete', FILTER_VALIDATE_INT);

    if (!$setIdToDelete) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID set untuk dihapus tidak valid.'];
        header("Location: ../manage_sets.php");
        exit;
    }

    $connection = connectToDatabase();
    if (!$connection) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
        header("Location: ../manage_sets.php");
        exit;
    }

    try {
        $setNameToDelete = 'Set ID: ' . $setIdToDelete;
        $stmtGetSet = $connection->prepare("SELECT set_name FROM instrument_sets WHERE set_id = ?");
        $stmtGetSet->bind_param("i", $setIdToDelete);
        $stmtGetSet->execute();
        if ($rowSet = $stmtGetSet->get_result()->fetch_assoc()) {
            $setNameToDelete = $rowSet['set_name'];
        }
        $stmtGetSet->close();

        // Dependency Check 1: Is this set used in ANY sterilization label, regardless of status?
        $stmtCheckLabels = $connection->prepare("SELECT record_id FROM sterilization_records WHERE item_id = ? AND item_type = 'set' LIMIT 1");
        $stmtCheckLabels->bind_param("i", $setIdToDelete);
        $stmtCheckLabels->execute();
        if ($stmtCheckLabels->get_result()->num_rows > 0) {
            throw new Exception("Set '" . htmlspecialchars($setNameToDelete) . "' tidak dapat dihapus karena memiliki riwayat pelabelan. Sebaiknya buat set baru daripada menghapus set yang sudah memiliki riwayat.");
        }
        $stmtCheckLabels->close();

        // Proceed with deletion
        $sqlDelete = "DELETE FROM instrument_sets WHERE set_id = ?";
        $stmtDelete = $connection->prepare($sqlDelete);
        $stmtDelete->bind_param("i", $setIdToDelete);
        if ($stmtDelete->execute()) {
            if ($stmtDelete->affected_rows > 0) {
                $successMessage = "Set instrumen '" . htmlspecialchars($setNameToDelete) . "' berhasil dihapus.";
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
                $logDetails = "Set instrumen dihapus: '" . htmlspecialchars($setNameToDelete) . "' (ID: " . $setIdToDelete . ").";
                log_activity('DELETE_SET', $loggedInUserId, $logDetails, 'set', $setIdToDelete);
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Set instrumen dengan ID " . $setIdToDelete . " tidak ditemukan."];
            }
        } else {
            throw new Exception("Gagal menghapus set instrumen: " . $stmtDelete->error);
        }
        $stmtDelete->close();

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    } finally {
        $connection->close();
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
}

header("Location: ../manage_sets.php");
exit;