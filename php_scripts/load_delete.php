<?php
/**
 * Delete Sterilization Load Script
 *
 * Handles the deletion of a sterilization load. Deletion is only
 * permitted if the load is still in 'persiapan' (preparation) status.
 * This version uses the standardized flash message system.
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

// --- Authorization & CSRF Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Silakan login.'];
    header("Location: ../manage_loads.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../manage_loads.php");
    exit;
}
$loggedInUserId = $_SESSION['user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loadIdToDelete = filter_input(INPUT_POST, 'load_id_to_delete', FILTER_VALIDATE_INT);

    if (!$loadIdToDelete) {
        // MODIFICATION: Use new flash message system
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Muatan tidak valid.'];
        header("Location: ../manage_loads.php");
        exit;
    }

    $conn = connectToDatabase();
    if (!$conn) {
        // MODIFICATION: Use new flash message system
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
        header("Location: ../manage_loads.php");
        exit;
    }

    try {
        // First, check the status of the load to ensure it can be deleted
        $sqlCheck = "SELECT status, load_name FROM sterilization_loads WHERE load_id = ?";
        if ($stmtCheck = $conn->prepare($sqlCheck)) {
            $stmtCheck->bind_param("i", $loadIdToDelete);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            if ($load = $resultCheck->fetch_assoc()) {
                if ($load['status'] !== 'persiapan') {
                    throw new Exception("Muatan '" . htmlspecialchars($load['load_name']) . "' tidak dapat dihapus karena statusnya bukan 'Persiapan'.");
                }

                // Proceed with deletion. Items in the load will be deleted automatically by the database cascade constraint.
                $sqlDelete = "DELETE FROM sterilization_loads WHERE load_id = ?";
                if ($stmtDelete = $conn->prepare($sqlDelete)) {
                    $stmtDelete->bind_param("i", $loadIdToDelete);
                    if ($stmtDelete->execute()) {
                        if ($stmtDelete->affected_rows > 0) {
                            // MODIFICATION: Use new flash message system
                            $successMessage = "Muatan '" . htmlspecialchars($load['load_name']) . "' berhasil dihapus.";
                            $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
                            log_activity('DELETE_LOAD', $loggedInUserId, "Muatan dihapus: " . $load['load_name'] . " (ID: " . $loadIdToDelete . ").");
                        } else {
                            throw new Exception("Muatan tidak ditemukan atau sudah dihapus.");
                        }
                    } else {
                        throw new Exception("Gagal mengeksekusi penghapusan: " . $stmtDelete->error);
                    }
                    $stmtDelete->close();
                } else {
                    throw new Exception("Gagal mempersiapkan statement penghapusan: " . $conn->error);
                }

            } else {
                throw new Exception("Muatan dengan ID " . $loadIdToDelete . " tidak ditemukan.");
            }
            $stmtCheck->close();
        } else {
            throw new Exception("Gagal memeriksa status muatan: " . $conn->error);
        }
    } catch (Exception $e) {
        // MODIFICATION: Use new flash message system
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
    } finally {
        $conn->close();
    }
} else {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
}

header("Location: ../manage_loads.php");
exit;