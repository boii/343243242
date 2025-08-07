<?php
/**
 * Update User Script
 *
 * Handles updating an existing user's details, including optional password change.
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

require_once '../config.php'; // Path ke config.php

// --- Authorization Check: Hanya Admin & Supervisor ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['admin', 'supervisor'])) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda tidak memiliki izin untuk mengubah data pengguna.'];
    $userIdForRedirect = $_POST['user_id'] ?? null;
    if ($userIdForRedirect && is_numeric($userIdForRedirect)) {
        header("Location: ../user_edit.php?user_id=" . urlencode((string)$userIdForRedirect));
    } else {
        header("Location: ../user_management.php");
    }
    exit;
}

// --- CSRF Token Check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid atau sesi mungkin telah berakhir. Silakan coba lagi.'];
    $userIdForRedirect = $_POST['user_id'] ?? null;
    if ($userIdForRedirect && is_numeric($userIdForRedirect)) {
        header("Location: ../user_edit.php?user_id=" . urlencode((string)$userIdForRedirect));
    } else {
        header("Location: ../user_management.php");
    }
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;

$errorMessages = [];
$formData = $_POST;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        // MODIFICATION: Use new flash message system
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Pengguna tidak valid untuk pembaruan.'];
        header("Location: ../user_management.php");
        exit;
    }
    $userIdToUpdate = (int)$_POST['user_id'];

    if (isset($_SESSION['user_id']) && $userIdToUpdate === (int)$_SESSION['user_id']) {
        // MODIFICATION: Use new flash message system
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak dapat mengedit akun Anda sendiri melalui proses ini. Gunakan halaman Profil.'];
        header("Location: ../user_edit.php?user_id=" . urlencode((string)$userIdToUpdate));
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmNewPassword = $_POST['confirm_password'] ?? '';
    $usernameForLog = trim($_POST['username'] ?? 'N/A');

    // --- Input Validation ---
    if (empty($fullName)) {
        $errorMessages[] = "Nama lengkap wajib diisi.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessages[] = "Format email tidak valid.";
    }
    
    if (empty($role) || !in_array($role, ['admin', 'staff', 'supervisor'])) {
        $errorMessages[] = "Peran pengguna tidak valid.";
    }
    
    // Admin cannot be edited by supervisor
    if ($role === 'admin' && $_SESSION['role'] !== 'admin') {
        $errorMessages[] = "Anda tidak memiliki izin untuk mengatur pengguna dengan peran Admin.";
    }

    $updatePassword = false;
    if (!empty($newPassword) || !empty($confirmNewPassword)) {
        if (empty($newPassword)) {
            $errorMessages[] = "Password baru tidak boleh kosong jika Anda ingin mengubahnya.";
        } elseif (strlen($newPassword) < 6) {
            $errorMessages[] = "Password baru minimal harus 6 karakter.";
        }
        if ($newPassword !== $confirmNewPassword) {
            $errorMessages[] = "Password baru dan konfirmasi password tidak cocok.";
        }
        if (empty($errorMessages)) {
            $updatePassword = true;
        }
    }

    if (empty($errorMessages)) {
        $conn = connectToDatabase();
        if (!$conn) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            $sqlParts = [];
            $types = "";
            $params = [];

            $sqlParts[] = "full_name = ?"; $types .= "s"; $params[] = $fullName;
            $sqlParts[] = "email = ?"; $types .= "s"; $params[] = !empty($email) ? $email : null;
            $sqlParts[] = "role = ?"; $types .= "s"; $params[] = $role;

            if ($updatePassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sqlParts[] = "password_hash = ?";
                $types .= "s";
                $params[] = $hashedPassword;
            }

            if (!empty($sqlParts)) {
                $sql = "UPDATE users SET " . implode(", ", $sqlParts) . " WHERE user_id = ?";
                $types .= "i";
                $params[] = $userIdToUpdate;

                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param($types, ...$params);
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            // MODIFICATION: Use new flash message system
                            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Data pengguna berhasil diperbarui.'];
                            $logDetails = "Data pengguna diperbarui untuk: " . $usernameForLog . " (ID: " . $userIdToUpdate . ").";
                            if($updatePassword) {
                                $logDetails .= " Password diubah.";
                            }
                            log_activity('UPDATE_USER', $loggedInUserId, $logDetails, 'user', $userIdToUpdate);

                        } else {
                            // MODIFICATION: Use new flash message system
                            $_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Tidak ada perubahan data terdeteksi.'];
                        }
                        header("Location: ../user_edit.php?user_id=" . urlencode((string)$userIdToUpdate));
                        exit;
                    } else {
                        $errorMessages[] = "Gagal memperbarui data pengguna: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errorMessages[] = "Gagal mempersiapkan statement pembaruan: " . $conn->error;
                }
            } else {
                // MODIFICATION: Use new flash message system
                $_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Tidak ada data yang perlu diperbarui.'];
                header("Location: ../user_edit.php?user_id=" . urlencode((string)$userIdToUpdate));
                exit;
            }
            $conn->close();
        }
    }
} else {
    $errorMessages[] = "Permintaan tidak valid.";
}

if (!empty($errorMessages)) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
    $_SESSION['form_data_user_edit'] = $formData;
}

$redirectUrl = "../user_management.php";
if (isset($formData['user_id']) && is_numeric($formData['user_id'])) {
    $redirectUrl = "../user_edit.php?user_id=" . urlencode((string)$formData['user_id']);
}
header("Location: " . $redirectUrl);
exit;
?>