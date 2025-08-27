<?php
/**
 * Update Application Settings Script
 *
 * This version removes the logic for saving public security settings and
 * the non-relevant staff permission for cycle validation. It now handles the
 * new traceability fields for thermal labels.
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
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Hanya admin yang dapat mengubah pengaturan.'];
    header("Location: ../settings.php");
    exit;
}

// --- CSRF Token Check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid atau sesi mungkin telah berakhir. Silakan coba lagi.'];
    header("Location: ../settings.php");
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$errorMessages = [];
$newLogoFilename = null; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $errorMessages[] = "Gagal membuat direktori 'uploads'. Pastikan izin folder sudah benar.";
        }
    }

    if (isset($_POST['delete_logo']) && $_POST['delete_logo'] === '1') {
        $currentLogo = $app_settings['app_logo_filename'] ?? '';
        if (!empty($currentLogo) && file_exists($uploadDir . $currentLogo)) {
            unlink($uploadDir . $currentLogo); 
        }
        $newLogoFilename = ''; 
    } 
    elseif (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['app_logo']['tmp_name'];
        $fileName = basename($_FILES['app_logo']['name']);
        $fileSize = $_FILES['app_logo']['size'];
        $maxFileSize = 2 * 1024 * 1024;

        if ($fileSize > $maxFileSize) {
            $errorMessages[] = "Ukuran file logo terlalu besar. Maksimal 2MB.";
        } else {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'];
            $fileMimeType = mime_content_type($fileTmpPath);
            
            if (in_array($fileMimeType, $allowedMimeTypes)) {
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $standardizedFilename = 'logo.' . $fileExtension;
                $targetPath = $uploadDir . $standardizedFilename;

                $currentLogo = $app_settings['app_logo_filename'] ?? '';
                if (!empty($currentLogo) && file_exists($uploadDir . $currentLogo)) {
                    unlink($uploadDir . $currentLogo);
                }

                if (move_uploaded_file($fileTmpPath, $targetPath)) {
                    $newLogoFilename = $standardizedFilename; 
                } else {
                    $errorMessages[] = "Gagal memindahkan file logo yang diunggah. Periksa izin folder 'uploads'.";
                }
            } else {
                $errorMessages[] = "Format file logo tidak diizinkan. Harap unggah PNG, JPG, GIF, atau SVG.";
            }
        }
    }
    
    $appInstanceName = trim($_POST['app_instance_name'] ?? 'Sterilabel'); 
    if (empty($appInstanceName)) $appInstanceName = 'Sterilabel';

    $showAppNameBesideLogo = isset($_POST['show_app_name_beside_logo']) ? '1' : '0';
    $defaultExpiryDays = filter_var(trim($_POST['default_expiry_days'] ?? '30'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3650]]);
    if ($defaultExpiryDays === false) {
        $errorMessages[] = "Durasi kedaluwarsa default tidak valid (harus antara 1 dan 3650 hari).";
        $defaultExpiryDays = 30; 
    }
    
    $showStatusBlockOnDetailPage = isset($_POST['show_status_block_on_detail_page']) ? '1' : '0';
    
    $printTemplate = trim($_POST['print_template'] ?? 'normal');
    if (!in_array($printTemplate, ['normal', 'half'])) {
        $printTemplate = 'normal';
    }

    $thermalFieldsConfigInput = $_POST['thermal_fields_config'] ?? [];
    $thermalQrPosition = trim($_POST['thermal_qr_position'] ?? 'bottom_center');
    $thermalQrSize = trim($_POST['thermal_qr_size'] ?? 'medium');
    $staffCanManageInstruments = isset($_POST['staff_can_manage_instruments']) ? '1' : '0';
    $staffCanManageSets = isset($_POST['staff_can_manage_sets']) ? '1' : '0';
    $staffCanViewActivityLog = isset($_POST['staff_can_view_activity_log']) ? '1' : '0';
    $thermalCustomText1 = trim($_POST['thermal_custom_text_1'] ?? '');
    $thermalCustomText2 = trim($_POST['thermal_custom_text_2'] ?? '');
    $thermalPaperWidth = filter_var(trim($_POST['thermal_paper_width_mm'] ?? '70'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 200]]);
    $thermalPaperHeight = filter_var(trim($_POST['thermal_paper_height_mm'] ?? '40'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 200]]);

    $allowedQrPositions = ['bottom_center', 'top_center', 'top_left_aligned', 'top_right_aligned', 'middle_left', 'middle_right'];
    if (!in_array($thermalQrPosition, $allowedQrPositions)) { $thermalQrPosition = 'bottom_center'; }
    $allowedQrSizes = ['small', 'medium', 'large'];
    if (!in_array($thermalQrSize, $allowedQrSizes)) { $thermalQrSize = 'medium'; }
    if ($thermalPaperWidth === false) { $thermalPaperWidth = 70; }
    if ($thermalPaperHeight === false) { $thermalPaperHeight = 40; }

    $processedThermalFieldsConfig = [];
    $validFieldKeys = [
        'item_name', 'label_title', 'created_at', 'expiry_date', 'label_unique_id',
        'load_name', 'cycle_number', 'machine_name', 'creator_username', 'used_at', 'notes',
        'custom_text_1', 'custom_text_2', 'cycle_operator_name', 'cycle_date', 'load_creator_name', 'destination_department_name'
    ];
    foreach ($validFieldKeys as $key) {
        $fieldInputData = $thermalFieldsConfigInput[$key] ?? [];
        $processedThermalFieldsConfig[$key] = [
            'visible' => isset($fieldInputData['visible']) && $fieldInputData['visible'] === '1',
            'order' => (int)($fieldInputData['order'] ?? 999),
            'hide_label' => isset($fieldInputData['hide_label']) && $fieldInputData['hide_label'] === '1',
            'custom_label' => trim($fieldInputData['custom_label'] ?? '') 
        ];
    }
    $thermalFieldsJson = json_encode($processedThermalFieldsConfig);

    if (empty($errorMessages)) {
        $conn = connectToDatabase();
        if (!$conn) {
            $errorMessages[] = "Koneksi database gagal.";
        } else {
            $settingsToUpdate = [
                'app_instance_name' => $appInstanceName,
                'default_expiry_days' => (string)$defaultExpiryDays,
                'show_status_block_on_detail_page' => $showStatusBlockOnDetailPage,
                'show_app_name_beside_logo' => $showAppNameBesideLogo,
                'print_template' => $printTemplate,
                'thermal_fields_config' => $thermalFieldsJson, 
                'thermal_qr_position' => $thermalQrPosition,
                'thermal_qr_size' => $thermalQrSize, 
                'staff_can_manage_instruments' => $staffCanManageInstruments,
                'staff_can_manage_sets' => $staffCanManageSets,
                'staff_can_view_activity_log' => $staffCanViewActivityLog,
                'thermal_custom_text_1' => $thermalCustomText1,
                'thermal_custom_text_2' => $thermalCustomText2,
                'thermal_paper_width_mm' => (string)$thermalPaperWidth,
                'thermal_paper_height_mm' => (string)$thermalPaperHeight,
            ];

            if ($newLogoFilename !== null) {
                $settingsToUpdate['app_logo_filename'] = $newLogoFilename;
            }
            
            $conn->begin_transaction();
            try {
                foreach ($settingsToUpdate as $settingName => $settingValue) {
                    $sqlUpsert = "INSERT INTO app_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                    if ($stmtUpsert = $conn->prepare($sqlUpsert)) {
                        $stmtUpsert->bind_param("ss", $settingName, $settingValue);
                        if (!$stmtUpsert->execute()) { throw new Exception("Gagal menyimpan pengaturan '" . htmlspecialchars($settingName) . "': " . $stmtUpsert->error); }
                        $stmtUpsert->close();
                    } else { throw new Exception("Gagal mempersiapkan statement untuk '" . htmlspecialchars($settingName) . "': " . $conn->error); }
                }
                
                $conn->query("DELETE FROM app_settings WHERE setting_name IN ('enable_public_actions', 'enable_public_pin', 'public_usage_pin', 'staff_can_validate_cycles')");

                $conn->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Pengaturan berhasil disimpan.'];
            } catch (Exception $e) {
                if($conn->inTransaction) $conn->rollback(); 
                $errorMessages[] = $e->getMessage();
            } finally {
                 if($conn->inTransaction && !empty($errorMessages)) { $conn->rollback(); }
                 $conn->close();
            }
        }
    }
} else {
    $errorMessages[] = "Permintaan tidak valid.";
}

if (!empty($errorMessages)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $errorMessages];
}

header("Location: ../settings.php");
exit;