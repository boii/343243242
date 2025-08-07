<?php
/**
 * Add New Instrument Script (with Image Upload & Auto-Code)
 *
 * This version handles file uploads, an auto-filled (but editable) code,
 * and enforces department as a required field.
 * It now also processes the new 'expiry_in_days' field.
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
$userRole = $_SESSION['role'] ?? null;
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');

if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !(in_array($userRole, ['admin', 'supervisor']) || ($userRole === 'staff' && $staffCanManageInstruments))
) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../manage_instruments.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../manage_instruments.php");
    exit;
}

$errorMessages = [];
$formData = $_POST;
$loggedInUserId = $_SESSION['user_id'] ?? null;
$newImageFilename = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- Sanitasi dan Validasi Input ---
    $instrumentName = trim($_POST['instrument_name'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $instrumentTypeId = filter_input(INPUT_POST, 'instrument_type_id', FILTER_VALIDATE_INT);
    $departmentId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
    // --- Perubahan: Logika kode disederhanakan, kini selalu diterima dari form ---
    $instrumentCode = trim($_POST['instrument_code'] ?? '');

    $expiryInDaysRaw = trim($_POST['expiry_in_days'] ?? '');
    $expiryInDays = null;
    if ($expiryInDaysRaw !== '') {
        $expiryInDays = filter_var($expiryInDaysRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($expiryInDays === false) {
            $errorMessages[] = "Masa kedaluwarsa standar harus berupa angka positif.";
        }
    }

    if (empty($instrumentName)) {
        $errorMessages[] = "Nama instrumen wajib diisi.";
    }
    // --- Perubahan: Kode kini wajib ---
    if (empty($instrumentCode)) {
        $errorMessages[] = "Kode instrumen wajib diisi.";
    }
    if (empty($instrumentTypeId)) {
        $errorMessages[] = "Tipe/Kategori instrumen wajib dipilih.";
    }
    if (empty($departmentId)) {
        $errorMessages[] = "Departemen/Unit wajib diisi.";
    }

    // --- Logika Upload Gambar ---
    if (isset($_FILES['instrument_image']) && $_FILES['instrument_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/instruments/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $errorMessages[] = "Gagal membuat direktori 'uploads/instruments/'. Pastikan izin folder sudah benar.";
            }
        }

        $fileTmpPath = $_FILES['instrument_image']['tmp_name'];
        $fileName = basename($_FILES['instrument_image']['name']);
        $fileSize = $_FILES['instrument_image']['size'];
        
        $maxFileSize = 100 * 1024; // 100 KB

        if ($fileSize > $maxFileSize) {
            $errorMessages[] = "Ukuran file gambar terlalu besar. Maksimal 100 KB.";
        } else {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileMimeType = mime_content_type($fileTmpPath);
            
            if (in_array($fileMimeType, $allowedMimeTypes)) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newImageFilename = uniqid('inst_', true) . '.' . $fileExtension;
                $targetPath = $uploadDir . $newImageFilename;

                if (!move_uploaded_file($fileTmpPath, $targetPath)) {
                    $errorMessages[] = "Gagal memindahkan file gambar yang diunggah.";
                    $newImageFilename = null;
                }
            } else {
                $errorMessages[] = "Format file gambar tidak diizinkan. Harap unggah PNG, JPG, GIF, atau WebP.";
            }
        }
    }

    // --- Proses ke Database ---
    if (empty($errorMessages)) {
        $connection = connectToDatabase();
        if (!$connection) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            // Validasi keunikan kode instrumen
            $sqlCheckCode = "SELECT instrument_id FROM instruments WHERE instrument_code = ?";
            if ($stmtCheck = $connection->prepare($sqlCheckCode)) {
                $stmtCheck->bind_param("s", $instrumentCode);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    $errorMessages[] = "Kode instrumen '" . htmlspecialchars($instrumentCode) . "' sudah digunakan.";
                }
                $stmtCheck->close();
            }

            if (empty($errorMessages)) {
                $sqlInsert = "INSERT INTO instruments (instrument_name, instrument_code, notes, created_by_user_id, instrument_type_id, department_id, image_filename, expiry_in_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                if ($stmtInsert = $connection->prepare($sqlInsert)) {
                    $notesToInsert = !empty($notes) ? $notes : null;
                    $stmtInsert->bind_param("sssiiisi", $instrumentName, $instrumentCode, $notesToInsert, $loggedInUserId, $instrumentTypeId, $departmentId, $newImageFilename, $expiryInDays);
                    
                    if ($stmtInsert->execute()) {
                        $newInstrumentId = $stmtInsert->insert_id;
                        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Instrumen '" . htmlspecialchars($instrumentName) . "' berhasil ditambahkan."];
                        log_activity('CREATE_INSTRUMENT', $loggedInUserId, "Instrumen baru ditambahkan: '" . htmlspecialchars($instrumentName) . "'.", 'instrument', $newInstrumentId);
                        unset($_SESSION['form_data_instrument_add']);
                        header("Location: ../manage_instruments.php");
                        exit;
                    } else {
                        $errorMessages[] = "Gagal menyimpan instrumen: " . $stmtInsert->error;
                        if ($newImageFilename && file_exists('../uploads/instruments/' . $newImageFilename)) {
                            unlink('../uploads/instruments/' . $newImageFilename);
                        }
                    }
                    $stmtInsert->close();
                } else {
                    $errorMessages[] = "Gagal mempersiapkan statement: " . $connection->error;
                }
            }
            $connection->close();
        }
    }
}

if (!empty($errorMessages)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
    $_SESSION['form_data_instrument_add'] = $formData;
}
header("Location: ../manage_instruments.php");
exit;