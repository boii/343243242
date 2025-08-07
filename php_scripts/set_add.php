<?php
/**
 * Add New Instrument Set Script (Supervisor Access Enabled & Expiry Days) - Revamped for Two-Step Workflow
 *
 * This version handles the first step of the set creation process.
 * It creates the basic set entry and then redirects to the edit page
 * for the second step (adding instruments). It now requires a set code.
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

// --- Authorization Check ---
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    !($_SESSION["role"] === 'admin' || $_SESSION["role"] === 'supervisor' || ($staffCanManageSets && $_SESSION["role"] === 'staff'))
) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../manage_sets.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../set_create.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;

$errorMessages = [];
$formData = $_POST;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $setName = trim($_POST['set_name'] ?? '');
    $setCode = trim($_POST['set_code'] ?? ''); // Kode sekarang wajib
    $specialInstructions = trim($_POST['special_instructions'] ?? '');

    $expiryInDaysRaw = trim($_POST['expiry_in_days'] ?? '');
    $expiryInDays = null;
    if ($expiryInDaysRaw !== '') {
        $expiryInDays = filter_var($expiryInDaysRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($expiryInDays === false) {
            $errorMessages[] = "Masa kedaluwarsa standar harus berupa angka positif.";
        }
    }

    if (empty($setName)) {
        $errorMessages[] = "Nama set wajib diisi.";
    }
    // --- Perubahan: Validasi Kode Set Wajib Diisi ---
    if (empty($setCode)) {
        $errorMessages[] = "Kode set wajib diisi.";
    }

    if (empty($errorMessages)) {
        $conn = connectToDatabase();
        if (!$conn) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            try {
                // Pengecekan duplikasi kode tetap dilakukan
                $stmtCheck = $conn->prepare("SELECT set_id FROM instrument_sets WHERE set_code = ?");
                $stmtCheck->bind_param("s", $setCode);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    throw new Exception("Kode set '" . htmlspecialchars($setCode) . "' sudah digunakan.");
                }
                $stmtCheck->close();
                
                $stmtSet = $conn->prepare("INSERT INTO instrument_sets (set_name, set_code, special_instructions, expiry_in_days, created_by_user_id) VALUES (?, ?, ?, ?, ?)");
                $instructionsToInsert = !empty($specialInstructions) ? $specialInstructions : null;
                
                $stmtSet->bind_param("sssii", $setName, $setCode, $instructionsToInsert, $expiryInDays, $loggedInUserId);
                if (!$stmtSet->execute()) {
                    throw new Exception("Gagal menyimpan data set: " . $stmtSet->error);
                }
                $newSetId = $stmtSet->insert_id;
                $stmtSet->close();

                $logDetails = "Set instrumen baru dibuat (langkah 1): '" . $setName . "' (Kode: " . $setCode . ").";
                log_activity('CREATE_SET', $loggedInUserId, $logDetails, 'set', $newSetId);
                
                unset($_SESSION['form_data_set_add']);
                
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Set '" . htmlspecialchars($setName) . "' berhasil dibuat. Sekarang, silakan pilih isi instrumennya."];
                header("Location: ../set_edit.php?set_id=" . $newSetId . "&new=true");
                exit;

            } catch (Exception $e) {
                $errorMessages[] = $e->getMessage();
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
    $_SESSION['form_data_set_add'] = $formData;
    header("Location: ../set_create.php");
    exit;
}