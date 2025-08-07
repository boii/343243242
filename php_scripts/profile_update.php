<?php
/**
 * Update User Profile Script (with Simplified Logic)
 *
 * This version simplifies the password update logic and improves user feedback
 * consistency by using the standard flash message system.
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
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["user_id"])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Sesi tidak valid.'];
    header("Location: ../profile.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid. Silakan coba lagi.'];
    header("Location: ../profile.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = (int)$_SESSION['user_id'];


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errorMessages = [];
    $formData = $_POST;

    // Sanitize and validate inputs
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';
    $currentPasswordInput = $_POST['current_password'] ?? '';
    
    if (empty($fullName)) {
        $errorMessages[] = "Nama lengkap wajib diisi.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessages[] = "Format email tidak valid.";
    }

    $isPasswordChangeAttempt = !empty($newPassword);

    if ($isPasswordChangeAttempt) {
        if (empty($currentPasswordInput)) {
            $errorMessages[] = "Password saat ini wajib diisi untuk mengubah password.";
        }
        if (strlen($newPassword) < 6) {
            $errorMessages[] = "Password baru minimal harus 6 karakter.";
        }
        if ($newPassword !== $confirmNewPassword) {
            $errorMessages[] = "Password baru dan konfirmasi password tidak cocok.";
        }
    }

    if (empty($errorMessages)) {
        $conn = connectToDatabase();
        if (!$conn) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            try {
                // Prepare the base update for non-password fields
                $sqlParts = ["full_name = ?", "email = ?"];
                $types = "ss";
                $paramsToBind = [$fullName, !empty($email) ? $email : null];

                // If changing password, first verify the current password
                if ($isPasswordChangeAttempt) {
                    $stmtVerify = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                    $stmtVerify->bind_param("i", $loggedInUserId);
                    $stmtVerify->execute();
                    $resultVerify = $stmtVerify->get_result();
                    if ($user = $resultVerify->fetch_assoc()) {
                        if (!password_verify($currentPasswordInput, $user['password_hash'])) {
                            throw new Exception("Password saat ini yang Anda masukkan salah.");
                        }
                    } else {
                        throw new Exception("Gagal memverifikasi pengguna.");
                    }
                    $stmtVerify->close();
                    
                    // Add password field to the update query
                    $sqlParts[] = "password_hash = ?";
                    $types .= "s";
                    $paramsToBind[] = password_hash($newPassword, PASSWORD_DEFAULT);
                }

                // Construct and execute the final UPDATE statement
                $sql = "UPDATE users SET " . implode(", ", $sqlParts) . " WHERE user_id = ?";
                $types .= "i";
                $paramsToBind[] = $loggedInUserId;
                
                $stmtUpdate = $conn->prepare($sql);
                $stmtUpdate->bind_param($types, ...$paramsToBind);
                
                if ($stmtUpdate->execute()) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Profil berhasil diperbarui.'];
                    // Update session with new name
                    $_SESSION['full_name'] = $fullName;
                    log_activity('UPDATE_PROFILE', $loggedInUserId, 'Detail profil diperbarui' . ($isPasswordChangeAttempt ? ' (termasuk password).' : '.'));
                } else {
                    throw new Exception("Gagal memperbarui profil di database.");
                }
                $stmtUpdate->close();

            } catch (Exception $e) {
                $errorMessages[] = $e->getMessage();
            } finally {
                $conn->close();
            }
        }
    }

    if (!empty($errorMessages)) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
        // Repopulate form data on error, except for passwords
        unset($formData['current_password'], $formData['new_password'], $formData['confirm_new_password']);
        $_SESSION['form_data_profile'] = $formData;
    }
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid.'];
}

header("Location: ../profile.php");
exit;
