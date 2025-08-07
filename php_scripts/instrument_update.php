<?php
/**
 * Update Instrument Script (with Image Upload & Required Dept)
 *
 * This version handles optional file uploads for replacing an instrument's image,
 * cleans up old image files, and enforces department as a required field.
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
$instrumentId = filter_input(INPUT_POST, 'instrument_id', FILTER_VALIDATE_INT);
$redirectUrl = $instrumentId ? "../instrument_edit.php?instrument_id=" . $instrumentId : "../manage_instruments.php";

if (!$instrumentId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Instrumen tidak valid untuk pembaruan.'];
    header("Location: ../manage_instruments.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- Sanitasi dan Validasi Input ---
    $instrumentName = trim($_POST['instrument_name'] ?? '');
    $instrumentCode = trim($_POST['instrument_code'] ?? ''); 
    $notes = trim($_POST['notes'] ?? '');
    $instrumentTypeId = filter_input(INPUT_POST, 'instrument_type_id', FILTER_VALIDATE_INT);
    $departmentId = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
    $deleteImage = isset($_POST['delete_image']) && $_POST['delete_image'] === '1';

    $expiryInDaysRaw = trim($_POST['expiry_in_days'] ?? '');
    $expiryInDays = null;
    if ($expiryInDaysRaw !== '') {
        $expiryInDays = filter_var($expiryInDaysRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($expiryInDays === false) {
            $errorMessages[] = "Masa kedaluwarsa standar harus berupa angka positif.";
        }
    }

    if (empty($instrumentName)) $errorMessages[] = "Nama instrumen wajib diisi.";
    if (empty($instrumentTypeId)) $errorMessages[] = "Tipe/Kategori instrumen wajib dipilih.";
    if (empty($departmentId)) $errorMessages[] = "Departemen/Unit wajib diisi.";

    $newImageFilename = null;

    $conn = connectToDatabase();
    if (!$conn) {
        $errorMessages[] = "Koneksi database gagal.";
    } else {
        // Ambil nama file gambar saat ini dari database
        $stmt_get_img = $conn->prepare("SELECT image_filename FROM instruments WHERE instrument_id = ?");
        $stmt_get_img->bind_param("i", $instrumentId);
        $stmt_get_img->execute();
        $currentImageFilename = $stmt_get_img->get_result()->fetch_assoc()['image_filename'] ?? null;
        $stmt_get_img->close();

        // --- Logika Upload/Delete Gambar ---
        $uploadDir = '../uploads/instruments/';

        if ($deleteImage) {
            if (!empty($currentImageFilename) && file_exists($uploadDir . $currentImageFilename)) {
                unlink($uploadDir . $currentImageFilename);
            }
            $newImageFilename = ''; // Set ke string kosong untuk diupdate di DB
        } elseif (isset($_FILES['instrument_image']) && $_FILES['instrument_image']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($uploadDir)) { if (!mkdir($uploadDir, 0755, true)) { $errorMessages[] = "Gagal membuat direktori 'uploads/instruments/'."; } }
            
            $fileTmpPath = $_FILES['instrument_image']['tmp_name'];
            $fileName = basename($_FILES['instrument_image']['name']);
            $fileSize = $_FILES['instrument_image']['size'];
            
            $maxFileSize = 100 * 1024; // 100 KB

            if ($fileSize > $maxFileSize) { $errorMessages[] = "Ukuran file gambar terlalu besar (Maks 100KB)."; }
            
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (empty($errorMessages) && !in_array(mime_content_type($fileTmpPath), $allowedMimeTypes)) { $errorMessages[] = "Format file gambar tidak valid."; }

            if (empty($errorMessages)) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newImageFilename = uniqid('inst_', true) . '.' . $fileExtension;
                $targetPath = $uploadDir . $newImageFilename;
                if (!move_uploaded_file($fileTmpPath, $targetPath)) {
                    $errorMessages[] = "Gagal memindahkan file gambar yang diunggah.";
                    $newImageFilename = null;
                } else {
                    if (!empty($currentImageFilename) && file_exists($uploadDir . $currentImageFilename)) {
                        unlink($uploadDir . $currentImageFilename);
                    }
                }
            }
        }
    }

    // --- Proses ke Database ---
    if (empty($errorMessages)) {
        if (!empty($instrumentCode)) {
            $sqlCheckCode = "SELECT instrument_id FROM instruments WHERE instrument_code = ? AND instrument_id != ?";
            if ($stmtCheck = $conn->prepare($sqlCheckCode)) {
                $stmtCheck->bind_param("si", $instrumentCode, $instrumentId);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    $errorMessages[] = "Kode instrumen '" . htmlspecialchars($instrumentCode) . "' sudah digunakan oleh instrumen lain.";
                }
                $stmtCheck->close();
            }
        }

        if (empty($errorMessages)) {
            $sqlParts = [
                "instrument_name = ?",
                "instrument_code = ?",
                "notes = ?",
                "instrument_type_id = ?",
                "department_id = ?",
                "expiry_in_days = ?",
                "updated_at = NOW()"
            ];
            $params = [
                $instrumentName,
                !empty($instrumentCode) ? $instrumentCode : null,
                !empty($notes) ? $notes : null,
                $instrumentTypeId,
                $departmentId,
                $expiryInDays
            ];
            $types = "sssiii";

            if ($newImageFilename !== null || $deleteImage) {
                $sqlParts[] = "image_filename = ?";
                $params[] = $newImageFilename;
                $types .= "s";
            }

            $params[] = $instrumentId;
            $types .= "i";
            
            $sqlUpdate = "UPDATE instruments SET " . implode(", ", $sqlParts) . " WHERE instrument_id = ?";

            if ($stmtUpdate = $conn->prepare($sqlUpdate)) {
                $stmtUpdate->bind_param($types, ...$params);
                
                if ($stmtUpdate->execute()) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Instrumen '" . htmlspecialchars($instrumentName) . "' berhasil diperbarui."];
                    $logDetails = "Data instrumen diperbarui untuk: '" . htmlspecialchars($instrumentName) . "' (ID: " . $instrumentId . ").";
                    log_activity('UPDATE_INSTRUMENT', $loggedInUserId, $logDetails, 'instrument', $instrumentId);
                    unset($_SESSION['form_data_instrument_edit']);
                } else {
                    $errorMessages[] = "Gagal memperbarui instrumen: " . $stmtUpdate->error;
                }
                $stmtUpdate->close();
            } else {
                $errorMessages[] = "Gagal mempersiapkan statement pembaruan: " . $conn->error;
            }
        }
        if (isset($conn)) $conn->close();
    }
} else {
    $errorMessages[] = "Permintaan tidak valid.";
}

if (!empty($errorMessages)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
    $_SESSION['form_data_instrument_edit'] = $formData;
}

header("Location: ../instrument_edit.php?instrument_id=" . $instrumentId);
exit;