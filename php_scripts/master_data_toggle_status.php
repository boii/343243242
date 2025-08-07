<?php
/**
 * Toggle Master Data Status Script (with Dependency Checks & Output Buffering)
 *
 * This version is simplified by removing logic for methods and sessions.
 * It also includes dependency checks to ensure data integrity.
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

ob_start();

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
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || empty($masterType)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Data tidak valid untuk mengubah status.'];
    header("Location: ../manage_master_data.php");
    exit;
}

$config = [
    'type' => [
        'table' => 'instrument_types', 'id_col' => 'type_id', 'name_col' => 'type_name', 'log_action' => 'TOGGLE_INSTRUMENT_TYPE_STATUS',
        'check' => ['table' => 'instruments', 'fk_col' => 'instrument_type_id', 'entity' => 'instrumen']
    ],
    'department' => [
        'table' => 'departments', 'id_col' => 'department_id', 'name_col' => 'department_name', 'log_action' => 'TOGGLE_DEPARTMENT_STATUS',
        'check' => ['table' => 'instruments', 'fk_col' => 'department_id', 'entity' => 'instrumen atau muatan']
    ],
    'machine' => [
        'table' => 'machines', 'id_col' => 'machine_id', 'name_col' => 'machine_name', 'log_action' => 'TOGGLE_MACHINE_STATUS',
        'check' => ['table' => 'sterilization_loads', 'fk_col' => 'machine_id', 'entity' => 'muatan aktif']
    ]
];

if (!array_key_exists($masterType, $config)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Tipe master data tidak valid.'];
    header("Location: ../manage_master_data.php");
    exit;
}

$tableName = $config[$masterType]['table'];
$idColumn = $config[$masterType]['id_col'];
$checkConfig = $config[$masterType]['check'] ?? null;

$conn = connectToDatabase();
if (!$conn) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
    header("Location: ../manage_master_data.php");
    exit;
}

try {
    $stmtCheckStatus = $conn->prepare("SELECT is_active FROM {$tableName} WHERE {$idColumn} = ?");
    $stmtCheckStatus->bind_param("i", $id);
    $stmtCheckStatus->execute();
    $currentStatus = $stmtCheckStatus->get_result()->fetch_assoc()['is_active'] ?? null;
    $stmtCheckStatus->close();

    // Modifikasi untuk pengecekan dependensi
    if ($currentStatus === 1 && $checkConfig) {
        $checkTable = $checkConfig['table'];
        $checkFkCol = $checkConfig['fk_col'];
        $entityName = $checkConfig['entity'];
        $valueTypeIsString = ($checkConfig['value_type'] ?? 'int') === 'string';

        // Jika value type adalah string, kita harus mendapatkan nama metode terlebih dahulu
        $valueToCheck = $id;
        if ($valueTypeIsString) {
            $stmtGetName = $conn->prepare("SELECT {$config[$masterType]['name_col']} FROM {$tableName} WHERE {$idColumn} = ?");
            $stmtGetName->bind_param("i", $id);
            $stmtGetName->execute();
            $valueToCheck = $stmtGetName->get_result()->fetch_assoc()[$config[$masterType]['name_col']] ?? null;
            $stmtGetName->close();
        }

        if ($valueToCheck !== null) {
            $sqlCheck = "SELECT COUNT(*) as count FROM {$checkTable} WHERE {$checkFkCol} = ?";
            $stmtDep = $conn->prepare($sqlCheck);
            $stmtDep->bind_param($valueTypeIsString ? "s" : "i", $valueToCheck);
            $stmtDep->execute();
            $dependencyCount = (int)$stmtDep->get_result()->fetch_assoc()['count'];
            $stmtDep->close();
    
            if ($dependencyCount > 0) {
                throw new Exception("Tidak dapat menonaktifkan item ini karena masih digunakan oleh satu atau lebih {$entityName}.");
            }
        }
    }


    $sqlToggle = "UPDATE {$tableName} SET is_active = !is_active WHERE {$idColumn} = ?";
    $stmtToggle = $conn->prepare($sqlToggle);
    $stmtToggle->bind_param("i", $id);

    if ($stmtToggle->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Status berhasil diperbarui.'];
        log_activity('TOGGLE_MASTER_STATUS', $_SESSION['user_id'] ?? null, "Status diubah untuk {$masterType} ID: $id di tabel {$tableName}");
    } else {
        throw new Exception('Gagal memperbarui status: ' . $stmtToggle->error);
    }
    $stmtToggle->close();

} catch (Exception $e) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $e->getMessage()];
} finally {
    $conn->close();
}

ob_end_flush();
header("Location: ../manage_master_data.php");
exit;