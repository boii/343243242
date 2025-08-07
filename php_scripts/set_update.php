<?php
/**
 * Update Instrument Set Script
 *
 * Handles updating an existing instrument set and its associated items.
 * This version makes set_code a required field and redirects to the set list upon success.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
    !($_SESSION["role"] === 'admin' || $_SESSION["role"] === 'supervisor' || ($staffCanManageSets && $_SESSION["role"] === 'staff'))
) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../manage_sets.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../manage_sets.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$loggedInUserId = $_SESSION['user_id'] ?? null;

$errorMessages = [];
$setId = filter_input(INPUT_POST, 'set_id', FILTER_VALIDATE_INT);

if (!$setId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Set tidak valid.'];
    header("Location: ../manage_sets.php");
    exit;
}

// --- PERUBAHAN: Lokasi redirect default diubah ke halaman daftar ---
$redirectUrl = "../manage_sets.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Jika terjadi error, kita akan kembali ke halaman edit
    $redirectUrlOnError = "../set_edit.php?set_id=" . $setId;

    $setName = trim($_POST['set_name'] ?? '');
    $setCode = trim($_POST['set_code'] ?? '');
    $specialInstructions = trim($_POST['special_instructions'] ?? '');
    $instrumentIds = $_POST['instruments'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

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
    if (empty($setCode)) {
        $errorMessages[] = "Kode set wajib diisi.";
    }

    if (empty($errorMessages)) {
        $conn = connectToDatabase();
        if (!$conn) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            $conn->begin_transaction();
            try {
                // Periksa duplikasi kode set, kecuali untuk set itu sendiri
                $stmtCheck = $conn->prepare("SELECT set_id FROM instrument_sets WHERE set_code = ? AND set_id != ?");
                $stmtCheck->bind_param("si", $setCode, $setId);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    throw new Exception("Kode set '" . htmlspecialchars($setCode) . "' sudah digunakan oleh set lain.");
                }
                $stmtCheck->close();

                // Update detail dasar dari set
                $stmtSet = $conn->prepare("UPDATE instrument_sets SET set_name = ?, set_code = ?, special_instructions = ?, expiry_in_days = ? WHERE set_id = ?");
                $instructionsToUpdate = !empty($specialInstructions) ? $specialInstructions : null;
                $stmtSet->bind_param("sssii", $setName, $setCode, $instructionsToUpdate, $expiryInDays, $setId);
                if (!$stmtSet->execute()) {
                    throw new Exception("Gagal memperbarui detail set: " . $stmtSet->error);
                }
                $stmtSet->close();

                // Hapus semua item yang ada saat ini dari set untuk diganti dengan yang baru
                $stmtDelete = $conn->prepare("DELETE FROM instrument_set_items WHERE set_id = ?");
                $stmtDelete->bind_param("i", $setId);
                if (!$stmtDelete->execute()) {
                    throw new Exception("Gagal menghapus item lama dari set: " . $stmtDelete->error);
                }
                $stmtDelete->close();

                // Masukkan kembali item-item yang dipilih
                if (!empty($instrumentIds) && is_array($instrumentIds)) {
                    $stmtItem = $conn->prepare("INSERT INTO instrument_set_items (set_id, instrument_id, quantity) VALUES (?, ?, ?)");
                    foreach ($instrumentIds as $instrumentId) {
                        $instrumentId = (int)$instrumentId;
                        $quantity = (int)($quantities[$instrumentId] ?? 1);
                        if ($quantity < 1) $quantity = 1;

                        $stmtItem->bind_param("iii", $setId, $instrumentId, $quantity);
                        if (!$stmtItem->execute()) {
                            throw new Exception("Gagal menambahkan item (ID: $instrumentId) ke set: " . $stmtItem->error);
                        }
                    }
                    $stmtItem->close();
                }

                $conn->commit();
                
                // --- PERUBAHAN: Pesan sukses yang lebih spesifik ---
                $successMessage = "Set '" . htmlspecialchars($setName) . "' berhasil diperbarui.";
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
                
                $logDetails = "Data set diperbarui untuk: '" . $setName . "' (ID: " . $setId . ").";
                log_activity('UPDATE_SET', $loggedInUserId, $logDetails, 'set', $setId);

            } catch (Exception $e) {
                $conn->rollback();
                $errorMessages[] = $e->getMessage();
                $redirectUrl = $redirectUrlOnError; // Jika error, kembalikan ke halaman edit
            } finally {
                $conn->close();
            }
        }
    } else {
         $redirectUrl = $redirectUrlOnError; // Jika error validasi, kembalikan ke halaman edit
    }
} else {
    $errorMessages[] = "Permintaan tidak valid.";
}

if (!empty($errorMessages)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
}

header("Location: " . $redirectUrl);
exit;