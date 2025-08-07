<?php
/**
 * Add New User Script
 *
 * Handles the creation of new user accounts (admin/staff/supervisor)
 * and uses the standardized flash message system.
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

// Role check: Ensure only logged-in admins or supervisors can add users
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['admin', 'supervisor'])) {
    // PERUBAHAN: Menggunakan sistem flash message terpusat
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.'];
    header("Location: ../user_management.php");
    exit;
}

// --- CSRF Token Check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // PERUBAHAN: Menggunakan sistem flash message terpusat
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Permintaan tidak valid atau token CSRF salah. Silakan coba lagi.'];
    header("Location: ../user_management.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;


$errorMessages = [];
$formData = $_POST;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? ''); // Optional
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // --- Validation ---
    if (empty($fullName)) {
        $errorMessages[] = "Nama lengkap wajib diisi.";
    }
    if (empty($username)) {
        $errorMessages[] = "Username wajib diisi.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errorMessages[] = "Username hanya boleh berisi huruf, angka, dan underscore, dengan panjang 3-20 karakter.";
    }

    if (empty($password)) {
        $errorMessages[] = "Password wajib diisi.";
    } elseif (strlen($password) < 6) {
        $errorMessages[] = "Password minimal harus 6 karakter.";
    }

    if ($password !== $confirmPassword) {
        $errorMessages[] = "Password dan konfirmasi password tidak cocok.";
    }

    if (empty($role) || !in_array($role, ['admin', 'staff', 'supervisor'])) {
        $errorMessages[] = "Peran pengguna tidak valid.";
    }
    
    // Admin cannot be created by supervisor
    if ($role === 'admin' && $_SESSION['role'] !== 'admin') {
        $errorMessages[] = "Anda tidak memiliki izin untuk membuat pengguna dengan peran Admin.";
    }


    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessages[] = "Format email tidak valid.";
    }

    // If no validation errors, proceed to database operations
    if (empty($errorMessages)) {
        $connection = connectToDatabase();
        if (!$connection) {
            $errorMessages[] = "Koneksi database gagal. Silakan coba lagi nanti.";
        } else {
            // Check if username already exists
            $sqlCheckUser = "SELECT user_id FROM users WHERE username = ?";
            if ($stmtCheck = $connection->prepare($sqlCheckUser)) {
                $stmtCheck->bind_param("s", $username);
                $stmtCheck->execute();
                $stmtCheck->store_result();
                if ($stmtCheck->num_rows > 0) {
                    $errorMessages[] = "Username '" . htmlspecialchars($username) . "' sudah digunakan. Silakan pilih username lain.";
                }
                $stmtCheck->close();
            } else {
                $errorMessages[] = "Gagal memeriksa username: " . $connection->error;
            }

            // Check if email already exists (if provided and unique constraint exists)
            if (!empty($email) && empty($errorMessages)) { 
                $sqlCheckEmail = "SELECT user_id FROM users WHERE email = ?";
                if ($stmtCheckEmail = $connection->prepare($sqlCheckEmail)) {
                    $stmtCheckEmail->bind_param("s", $email);
                    $stmtCheckEmail->execute();
                    $stmtCheckEmail->store_result();
                    if ($stmtCheckEmail->num_rows > 0) {
                        $errorMessages[] = "Email '" . htmlspecialchars($email) . "' sudah digunakan.";
                    }
                    $stmtCheckEmail->close();
                } else {
                    $errorMessages[] = "Gagal memeriksa email: " . $connection->error;
                }
            }

            // If still no errors after checks, insert new user
            if (empty($errorMessages)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sqlInsert = "INSERT INTO users (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)";

                if ($stmtInsert = $connection->prepare($sqlInsert)) {
                    $emailToInsert = !empty($email) ? $email : null;
                    $stmtInsert->bind_param("sssss", $username, $passwordHash, $fullName, $emailToInsert, $role);

                    if ($stmtInsert->execute()) {
                        // PERUBAHAN: Menggunakan sistem flash message terpusat
                        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Pengguna '" . htmlspecialchars($username) . "' berhasil ditambahkan."];
                        
                        // Integrasi Logging
                        $newUserId = $stmtInsert->insert_id;
                        $logDetails = "Pengguna baru ditambahkan: " . $username . " (Nama: " . $fullName . ", Peran: " . $role . ").";
                        log_activity('CREATE_USER', $loggedInUserId, $logDetails, 'user', $newUserId);

                    } else {
                        $errorMessages[] = "Gagal menambahkan pengguna: " . $stmtInsert->error;
                    }
                    $stmtInsert->close();
                } else {
                    $errorMessages[] = "Gagal mempersiapkan statement penambahan pengguna: " . $connection->error;
                }
            }
            $connection->close();
        }
    }
} else {
    // Not a POST request, redirect or show error
    $errorMessages[] = "Permintaan tidak valid.";
}

// Store messages in session and redirect back to user_management.php
if (!empty($errorMessages)) {
    // PERUBAHAN: Menggunakan sistem flash message terpusat
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
    // Repopulate form data on error, except for passwords
    unset($formData['password'], $formData['confirm_password']);
    $_SESSION['form_data_user_add'] = $formData;
}

header("Location: ../user_management.php");
exit;