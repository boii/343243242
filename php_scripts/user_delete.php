<?php
/**
 * Delete User Script
 *
 * Handles the deletion of user accounts by an admin.
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

require_once '../config.php'; // Go up one level to find config.php

// --- Authorization Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['admin', 'supervisor'])) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.'];
    header("Location: ../user_management.php");
    exit;
}

// --- CSRF Token Check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid atau token CSRF salah. Silakan coba lagi.'];
    header("Location: ../user_management.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['user_id_to_delete']) && is_numeric($_POST['user_id_to_delete'])) {
        $userIdToDelete = (int)$_POST['user_id_to_delete'];
        $currentUserId = $_SESSION['user_id'] ?? 0;

        if ($userIdToDelete === $currentUserId) {
            // MODIFICATION: Use new flash message system
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
            header("Location: ../user_management.php");
            exit;
        }

        $connection = connectToDatabase();
        if (!$connection) {
            // MODIFICATION: Use new flash message system
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
            header("Location: ../user_management.php");
            exit;
        }

        try {
            // Get username for logging before deletion
            $usernameToDelete = 'Pengguna Tidak Dikenal';
            $sqlGetUser = "SELECT username FROM users WHERE user_id = ?";
            if ($stmtUser = $connection->prepare($sqlGetUser)) {
                $stmtUser->bind_param("i", $userIdToDelete);
                $stmtUser->execute();
                $resultUser = $stmtUser->get_result();
                if ($rowUser = $resultUser->fetch_assoc()) {
                    $usernameToDelete = $rowUser['username'];
                }
                $stmtUser->close();
            }

            $sqlDelete = "DELETE FROM users WHERE user_id = ?";
            if ($stmtDelete = $connection->prepare($sqlDelete)) {
                $stmtDelete->bind_param("i", $userIdToDelete);
                if ($stmtDelete->execute()) {
                    if ($stmtDelete->affected_rows > 0) {
                        // MODIFICATION: Use new flash message system
                        $successMessage = "Pengguna '" . htmlspecialchars($usernameToDelete) . "' (ID: " . $userIdToDelete . ") berhasil dihapus.";
                        $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
                        
                        // Integrasi Logging
                        $logDetails = "Pengguna dihapus: " . $usernameToDelete . " (ID: " . $userIdToDelete . ").";
                        log_activity('DELETE_USER', $loggedInUserId, $logDetails, 'user', $userIdToDelete);
                    } else {
                        // MODIFICATION: Use new flash message system
                        $_SESSION['flash_message'] = ['type' => 'info', 'text' => "Pengguna dengan ID " . $userIdToDelete . " tidak ditemukan atau sudah dihapus."];
                    }
                } else {
                     // MODIFICATION: Use new flash message system
                    $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Gagal menghapus pengguna: " . htmlspecialchars($stmtDelete->error)];
                }
                $stmtDelete->close();
            } else {
                 // MODIFICATION: Use new flash message system
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Gagal mempersiapkan statement penghapusan: " . htmlspecialchars($connection->error)];
            }
            $connection->close();
        } catch (Exception $e) {
            // MODIFICATION: Use new flash message system
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan server saat mencoba menghapus pengguna: " . $e->getMessage()];
        }
    } else {
        // MODIFICATION: Use new flash message system
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID pengguna untuk dihapus tidak valid atau tidak disediakan.'];
    }
} else {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
}

header("Location: ../user_management.php");
exit;