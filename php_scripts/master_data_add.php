<?php
/**
 * Add Master Data Script
 *
 * Handles adding new entries to master data tables (types, departments, machines).
 * This version is simplified by removing logic for methods and sessions.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../manage_master_data.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../manage_master_data.php");
    exit;
}

$masterType = $_POST['master_type'] ?? '';
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? ''); 

if (empty($name)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Nama tidak boleh kosong.'];
    header("Location: ../manage_master_data.php");
    exit;
}

$config = [
    'type' => [
        'table' => 'instrument_types',
        'columns' => ['type_name'],
        'log_action' => 'CREATE_INSTRUMENT_TYPE',
        'success_msg' => 'Tipe Instrumen',
    ],
    'department' => [
        'table' => 'departments',
        'columns' => ['department_name'],
        'log_action' => 'CREATE_DEPARTMENT',
        'success_msg' => 'Departemen',
    ],
    'machine' => [
        'table' => 'machines',
        'columns' => ['machine_name', 'machine_code'],
        'log_action' => 'CREATE_MACHINE',
        'success_msg' => 'Mesin',
    ],
];

if (!array_key_exists($masterType, $config)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Tipe master data tidak valid.'];
    header("Location: ../manage_master_data.php");
    exit;
}

$tableName = $config[$masterType]['table'];
$columns = $config[$masterType]['columns'];
$placeholders = implode(',', array_fill(0, count($columns), '?'));

$conn = connectToDatabase();
if (!$conn) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
} else {
    try {
        $sql = "INSERT INTO {$tableName} (" . implode(',', $columns) . ") VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);

        if ($masterType === 'machine') {
            if (empty($code)) {
                throw new Exception("Kode Mesin wajib diisi.");
            }
            $stmt->bind_param("ss", $name, $code);
        } else {
            $stmt->bind_param("s", $name);
        }

        if ($stmt->execute()) {
            $successMessage = $config[$masterType]['success_msg'] . " '" . htmlspecialchars($name) . "' berhasil ditambahkan.";
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => $successMessage];
            
            $logDetails = "Master data " . $config[$masterType]['success_msg'] . " baru ditambahkan: " . $name . (!empty($code) ? " (Kode: $code)" : "");
            log_activity($config[$masterType]['log_action'], $_SESSION['user_id'] ?? null, $logDetails);
        } else {
            if ($conn->errno === 1062) { // Duplicate entry
                $errorMessage = "Nama atau Kode '" . htmlspecialchars(!empty($code) ? $code : $name) . "' sudah ada.";
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessage];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Gagal menyimpan data: " . $stmt->error];
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Terjadi kesalahan: " . $e->getMessage()];
    } finally {
        $conn->close();
    }
}

header("Location: ../manage_master_data.php");
exit;