<?php
/**
 * Thermal Print Template Page
 *
 * Displays a label formatted for thermal printers.
 * This version is updated to correctly use the @page CSS rule for dynamic paper sizing.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Frontend
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once 'config.php'; 
if (file_exists('libs/phpqrcode/qrlib.php')) {
    require_once 'libs/phpqrcode/qrlib.php';
}
$qrLibMissing = !class_exists('QRcode');


$labelDetails = null; $itemDetails = null; $errorMessage = ''; $labelUniqueId = null;
$conn_print_thermal = null; 

// Ambil semua pengaturan dari $app_settings global
$thermalFieldsConfigFromDB = $app_settings['thermal_fields_config'] ?? [];
$thermalQrPosition = $app_settings['thermal_qr_position'] ?? 'bottom_center'; 
$thermalQrSizeSetting = $app_settings['thermal_qr_size'] ?? 'medium';
$thermalCustomText1Value = $app_settings['thermal_custom_text_1'] ?? '';
$thermalCustomText2Value = $app_settings['thermal_custom_text_2'] ?? '';
$thermalPaperWidthMM = (int)($app_settings['thermal_paper_width_mm'] ?? 70); 
$thermalPaperHeightMM = (int)($app_settings['thermal_paper_height_mm'] ?? 70); 
$labelInternalPadding = '2mm'; 

$thermalFieldsConfig = [ 
    'item_name' => ['visible' => true, 'order' => 1, 'label' => 'Nama Item', 'hide_label' => false, 'custom_label' => ''],
    'created_at' => ['visible' => true, 'order' => 3, 'label' => 'Tanggal Buat', 'hide_label' => false, 'custom_label' => ''],
    'expiry_date' => ['visible' => true, 'order' => 2, 'label' => 'Tanggal Kedaluwarsa', 'hide_label' => false, 'custom_label' => ''],
    'label_unique_id' => ['visible' => false, 'order' => 4, 'label' => 'ID Label Unik', 'hide_label' => false, 'custom_label' => ''],
    'label_title' => ['visible' => true, 'order' => 5, 'label' => 'Nama Label', 'hide_label' => false, 'custom_label' => ''],
    'used_at'           => ['visible' => false, 'order' => 6, 'label' => 'Tanggal Digunakan',  'hide_label' => false, 'custom_label' => ''],
    'validated_at'      => ['visible' => false, 'order' => 7, 'label' => 'Tanggal Divalidasi', 'hide_label' => false, 'custom_label' => ''],
    'validator_username'=> ['visible' => false, 'order' => 8, 'label' => 'Divalidasi Oleh',    'hide_label' => false, 'custom_label' => ''],
    'creator_username'  => ['visible' => false, 'order' => 9, 'label' => 'Dibuat Oleh',        'hide_label' => false, 'custom_label' => ''],
    'notes'             => ['visible' => false, 'order' => 10, 'label' => 'Catatan Tambahan',   'hide_label' => false, 'custom_label' => ''],
    'custom_text_1'     => ['visible' => false, 'order' => 11, 'label' => 'Info Kustom 1',      'hide_label' => true,  'custom_label' => ''], 
    'custom_text_2'     => ['visible' => false, 'order' => 12, 'label' => 'Info Kustom 2',     'hide_label' => true,  'custom_label' => '']  
];

if (is_array($thermalFieldsConfigFromDB)) {
    foreach ($thermalFieldsConfig as $key => &$defaultConfig) { 
        if (isset($thermalFieldsConfigFromDB[$key])) {
            $defaultConfig['visible'] = (bool)($thermalFieldsConfigFromDB[$key]['visible'] ?? $defaultConfig['visible']);
            $defaultConfig['order'] = (int)($thermalFieldsConfigFromDB[$key]['order'] ?? $defaultConfig['order']);
            $defaultConfig['hide_label'] = (bool)($thermalFieldsConfigFromDB[$key]['hide_label'] ?? $defaultConfig['hide_label']);
            $defaultConfig['custom_label'] = (string)($thermalFieldsConfigFromDB[$key]['custom_label'] ?? $defaultConfig['custom_label']);
        }
    }
    unset($defaultConfig); 
}

if (isset($_GET['label_uid'])) {
    $labelUniqueId = trim($_GET['label_uid']);
} else {
    $errorMessage = "ID Label tidak disediakan.";
}

if ($labelUniqueId && empty($errorMessage)) {
    $conn_print_thermal = connectToDatabase(); 
    if ($conn_print_thermal) {
        $sqlUpdateExpired = "UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW() AND label_unique_id = ?";
        if ($stmtUpdateExpired = $conn_print_thermal->prepare($sqlUpdateExpired)) {
            $stmtUpdateExpired->bind_param("s", $labelUniqueId);
            if (!$stmtUpdateExpired->execute()) {
                error_log("Error updating expired label in print_thermal.php (UID: " . $labelUniqueId . "): " . $stmtUpdateExpired->error);
            }
            $stmtUpdateExpired->close();
        } else {
            error_log("Error preparing statement to update expired label in print_thermal.php: " . $conn_print_thermal->error);
        }
    } else {
        error_log("Database connection failed for expiry check in print_thermal.php.");
        if(empty($errorMessage)) $errorMessage = "Koneksi database gagal."; 
    }
}

if ($labelUniqueId && empty($errorMessage)) { 
    if (!$conn_print_thermal || !($conn_print_thermal instanceof mysqli) || $conn_print_thermal->connect_error) { 
        $conn_print_thermal = connectToDatabase();
    }

    if ($conn_print_thermal) {
        $sql = "SELECT sr.record_id, sr.label_unique_id, sr.item_id, sr.item_type, sr.label_title, sr.created_by_user_id, sr.created_at, sr.expiry_date, sr.status, sr.notes, sr.used_at, sr.validated_at, u.username as creator_username, u.full_name as creator_full_name, val.username as validator_username, val.full_name as validator_full_name FROM sterilization_records sr LEFT JOIN users u ON sr.created_by_user_id = u.user_id LEFT JOIN users val ON sr.validated_by_user_id = val.user_id WHERE sr.label_unique_id = ?";
        if ($stmt = $conn_print_thermal->prepare($sql)) {
            $stmt->bind_param("s", $labelUniqueId); $stmt->execute(); $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $labelDetails = $result->fetch_assoc();
                if ($labelDetails['item_type'] === 'instrument') { $sqlItem = "SELECT instrument_name, instrument_code FROM instruments WHERE instrument_id = ?"; } 
                elseif ($labelDetails['item_type'] === 'set') { $sqlItem = "SELECT set_name, set_code FROM instrument_sets WHERE set_id = ?"; } 
                else { $sqlItem = null; }
                if ($sqlItem && $stmtItem = $conn_print_thermal->prepare($sqlItem)) {
                    $stmtItem->bind_param("i", $labelDetails['item_id']); $stmtItem->execute(); $resultItem = $stmtItem->get_result();
                    if ($itemRow = $resultItem->fetch_assoc()) { $itemDetails = $itemRow; } $stmtItem->close();
                }
            } else { if(empty($errorMessage)) $errorMessage = "Label tidak ditemukan."; } $stmt->close();
        } else { if(empty($errorMessage)) $errorMessage = "Gagal mempersiapkan statement: " . $conn_print_thermal->error; }
        
        if ($conn_print_thermal instanceof mysqli && $conn_print_thermal->thread_id) {
            $conn_print_thermal->close();
        }
    } else { if(empty($errorMessage)) $errorMessage = "Koneksi database gagal."; }
}

$fieldsToDisplay = array_filter($thermalFieldsConfig, function($field, $key) {
    return ($field['visible'] ?? false) && !in_array($key, ['custom_text_1', 'custom_text_2']);
}, ARRAY_FILTER_USE_BOTH);
uasort($fieldsToDisplay, function($a, $b) { return ($a['order'] ?? 999) <=> ($b['order'] ?? 999); });

$mainAreaFlexDirection = 'column'; $qrContainerFlexOrder = 1; $contentAreaFlexOrder = 0;
$qrContainerAlignSelf = 'center'; $contentBlockTextAlign = 'center'; $sectionItemsJustify = 'center'; 

if ($thermalQrPosition === 'top_center') { $qrContainerFlexOrder = 0; $contentAreaFlexOrder = 1; } 
elseif ($thermalQrPosition === 'top_left_aligned') { $qrContainerFlexOrder = 0; $contentAreaFlexOrder = 1; $qrContainerAlignSelf = 'flex-start'; $contentBlockTextAlign = 'left'; $sectionItemsJustify = 'flex-start'; }
elseif ($thermalQrPosition === 'top_right_aligned') { $qrContainerFlexOrder = 0; $contentAreaFlexOrder = 1; $qrContainerAlignSelf = 'flex-end'; $contentBlockTextAlign = 'right'; $sectionItemsJustify = 'flex-end'; }
elseif ($thermalQrPosition === 'middle_left') { $mainAreaFlexDirection = 'row'; $qrContainerFlexOrder = 0; $contentAreaFlexOrder = 1; $contentBlockTextAlign = 'left'; $sectionItemsJustify = 'flex-start';} 
elseif ($thermalQrPosition === 'middle_right') { $mainAreaFlexDirection = 'row'; $qrContainerFlexOrder = 1; $contentAreaFlexOrder = 0; $contentBlockTextAlign = 'left'; $sectionItemsJustify = 'flex-start';}

switch ($thermalQrSizeSetting) {
    case 'small': $qrImageSizeCSS = '25mm'; break; 
    case 'large': $qrImageSizeCSS = '35mm'; break; 
    case 'medium': default: $qrImageSizeCSS = '30mm'; break;
}
$qrPixelSize = 6;

$contentMaxWidth = '100%'; $gapBetweenQrAndText = '2mm';
if ($mainAreaFlexDirection === 'row') {
    $qrWidthNumeric = (int)preg_replace('/[^0-9]/', '', $qrImageSizeCSS);
    $gapNumeric = (int)preg_replace('/[^0-9]/', '', $gapBetweenQrAndText);
    $paddingNumeric = (float)preg_replace('/[^0-9.]/', '', $labelInternalPadding);
    $availableWidthForContentAndQr = $thermalPaperWidthMM - (2 * $paddingNumeric); 
    $contentWidthCalculated = $availableWidthForContentAndQr - $qrWidthNumeric - $gapNumeric; 
    $contentMaxWidth = $contentWidthCalculated > 0 ? $contentWidthCalculated . 'mm' : 'auto';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Thermal - <?php echo htmlspecialchars($labelUniqueId ?? 'Error'); ?></title>
    <style>
        @page { 
            size: <?php echo $thermalPaperWidthMM; ?>mm <?php echo $thermalPaperHeightMM; ?>mm; 
            margin: 0mm; 
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; padding: 0; 
            -webkit-print-color-adjust: exact !important; color-adjust: exact !important; 
            background-color: #e0e0e0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px; 
            box-sizing: border-box; 
        }
        .label-print-container-thermal {
            width: <?php echo $thermalPaperWidthMM; ?>mm; 
            height: <?php echo $thermalPaperHeightMM; ?>mm; 
            padding: <?php echo $labelInternalPadding; ?>; 
            box-sizing: border-box; 
            font-size: 12pt; 
            font-weight: bold;
            line-height: 1.15; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            background-color: #fff !important; 
            margin: 0; 
            border: 2px dashed #555; 
        }
        .top-custom-text-area {
            width: 100%;
            text-align: center;
            margin-bottom: 1mm; 
            flex-shrink: 0;
        }
        .custom-text-line-print {
            font-size: 12pt; 
            font-weight: bold;
            line-height: 1.15; 
            margin-bottom: 0.5mm;
        }
        .separator-line {
            width: 85%; 
            height: 0.5px;
            background-color: #333; 
            margin: 1mm auto 1.5mm auto; 
            flex-shrink: 0;
        }
        .main-label-area {
            display: flex;
            flex-direction: <?php echo $mainAreaFlexDirection; ?> !important; 
            flex-grow: 1; 
            width: 100%;
            overflow: hidden;
        }
        .label-thermal-content { 
            overflow: hidden; 
            display: flex;
            flex-direction: column;
            justify-content: center; 
            order: <?php echo $contentAreaFlexOrder; ?> !important;
            text-align: <?php echo $contentBlockTextAlign; ?>; 
            <?php if ($mainAreaFlexDirection === 'row'): ?>
                flex-grow: 1; 
                padding-left: <?php echo ($thermalQrPosition === 'middle_left') ? $gapBetweenQrAndText : '0'; ?>;
                padding-right: <?php echo ($thermalQrPosition === 'middle_right') ? $gapBetweenQrAndText : '0'; ?>;
                max-width: <?php echo $contentMaxWidth; ?>; 
            <?php else: ?>
                width: 100%; 
            <?php endif; ?>
        }
        .item-name-value-thermal { 
            font-size: 12pt; 
            margin-bottom: 1.5mm; line-height: 1.1; display: block;
            text-align: <?php echo $contentBlockTextAlign; ?>; 
             <?php if ($mainAreaFlexDirection === 'row'): ?> text-align: left; <?php endif; ?>
        }
        .label-thermal-section {
            margin-bottom: 1mm; 
            display: flex;
            justify-content: <?php echo $sectionItemsJustify; ?>; 
            flex-wrap: nowrap; align-items: baseline;
        }
        .label-thermal-section strong { 
            margin-right: 1mm; flex-shrink: 0; min-width: auto; 
        }
        .label-thermal-section span.value { 
            word-break: break-all; 
            text-align: left; 
        }
        .qr-code-container-thermal {
            text-align: center; flex-shrink: 0; 
            order: <?php echo $qrContainerFlexOrder; ?> !important;
            <?php if ($mainAreaFlexDirection === 'column'): ?>
                align-self: <?php echo $qrContainerAlignSelf; ?>; 
                margin-top: auto; padding-top: 1mm; width: auto; max-width:100%;
                 padding-bottom: <?php echo ($thermalQrPosition === 'top_center' || $thermalQrPosition === 'top_left_aligned' || $thermalQrPosition === 'top_right_aligned' ? '1mm' : '0'); ?>;
            <?php else: ?>
                display: flex; align-items: center; justify-content: center;
                width: <?php echo $qrImageSizeCSS; ?>; 
                height: 100%; 
            <?php endif; ?>
        }
        .qr-code-container-thermal img {
            max-width: <?php echo $qrImageSizeCSS; ?>; 
            max-height:<?php echo ($mainAreaFlexDirection === 'row' ? 'calc('.$thermalPaperHeightMM.'mm - 2 * '.$labelInternalPadding.')' : $qrImageSizeCSS); ?>;
            height: auto; display: block; 
        }
        .no-print-on-labelpage { display: none !important; }

        @media print {
            body { margin: 0 !important; padding: 0 !important; background-color: transparent !important; display: block !important; min-height: auto !important; }
            .label-print-container-thermal { border: none !important; margin: 0 auto !important; }
            .no-print-on-labelpage { display: none !important; }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(window.close, 1000);">
    <?php if ($labelDetails && ($itemDetails || ($labelDetails['item_type'] !== 'set' && $labelDetails['item_type'] !== 'instrument' ))): ?>
        <div class="label-print-container-thermal">
            <div class="top-custom-text-area">
                <?php if (isset($thermalFieldsConfig['custom_text_1']['visible']) && $thermalFieldsConfig['custom_text_1']['visible'] && !empty(trim($thermalCustomText1Value))): ?>
                    <div class="custom-text-line-print"><?php echo htmlspecialchars($thermalCustomText1Value); ?></div>
                <?php endif; ?>
                <?php if (isset($thermalFieldsConfig['custom_text_2']['visible']) && $thermalFieldsConfig['custom_text_2']['visible'] && !empty(trim($thermalCustomText2Value))): ?>
                    <div class="custom-text-line-print"><?php echo htmlspecialchars($thermalCustomText2Value); ?></div>
                <?php endif; ?>
            </div>

            <?php 
                $hasCustomText = (isset($thermalFieldsConfig['custom_text_1']['visible']) && $thermalFieldsConfig['custom_text_1']['visible'] && !empty(trim($thermalCustomText1Value))) ||
                                 (isset($thermalFieldsConfig['custom_text_2']['visible']) && $thermalFieldsConfig['custom_text_2']['visible'] && !empty(trim($thermalCustomText2Value)));
                $hasMainContent = !empty($fieldsToDisplay); 
            ?>
            <?php if ($hasCustomText && $hasMainContent): ?>
                <div class="separator-line"></div>
            <?php endif; ?>

            <div class="main-label-area">
                <div class="label-thermal-content">
                    <?php
                    foreach ($fieldsToDisplay as $fieldKey => $fieldConfig):
                        if ($fieldKey === 'custom_text_1' || $fieldKey === 'custom_text_2') { continue; }
                        $value = ''; $isItemName = ($fieldKey === 'item_name');
                        $currentFieldLabelText = !empty(trim((string)($fieldConfig['custom_label'] ?? ''))) ? htmlspecialchars($fieldConfig['custom_label']) : htmlspecialchars($fieldConfig['label']);
                        $hideThisLabel = (bool)($fieldConfig['hide_label'] ?? false);

                        switch ($fieldKey) {
                            case 'item_name':
                                if ($itemDetails) { 
                                    if ($labelDetails['item_type'] === 'instrument') { $value = $itemDetails['instrument_name']; }
                                    elseif ($labelDetails['item_type'] === 'set') { $value = $itemDetails['set_name']; }
                                } else { $value = 'N/A'; }
                                break;
                            case 'label_unique_id': $value = $labelDetails['label_unique_id'] ?? 'N/A'; break;
                            case 'label_title': $value = $labelDetails['label_title'] ?? 'N/A'; break; 
                            case 'creator_username': $value = $labelDetails['creator_full_name'] ?? ($labelDetails['creator_username'] ?? 'N/A'); break;
                            case 'created_at': $value = isset($labelDetails['created_at']) ? (new DateTime($labelDetails['created_at']))->format('d/m/y H:i') : 'N/A'; break;
                            case 'expiry_date':
                                $value = isset($labelDetails['expiry_date']) ? (new DateTime($labelDetails['expiry_date']))->format('d/m/y H:i') : 'N/A';
                                break; 
                            case 'used_at':
                                if (isset($labelDetails['status']) && strtolower($labelDetails['status']) === 'used' && !empty($labelDetails['used_at'])) {
                                    $value = (new DateTime($labelDetails['used_at']))->format('d/m/y H:i');
                                } else {
                                    if (!($fieldConfig['visible'] ?? false)) continue 2;
                                    $value = '-'; 
                                }
                                break;
                            case 'validated_at':
                                if (isset($labelDetails['status']) && strtolower($labelDetails['status']) === 'active' && !empty($labelDetails['validated_at'])) {
                                    $value = (new DateTime($labelDetails['validated_at']))->format('d/m/y H:i');
                                } else {
                                    if (!($fieldConfig['visible'] ?? false)) continue 2;
                                    $value = '-';
                                }
                                break;
                            case 'validator_username':
                                if (isset($labelDetails['status']) && strtolower($labelDetails['status']) === 'active' && !empty($labelDetails['validated_at'])) {
                                    $value = htmlspecialchars($labelDetails['validator_full_name'] ?? ($labelDetails['validator_username'] ?? 'N/A'));
                                } else {
                                    if (!($fieldConfig['visible'] ?? false)) continue 2;
                                    $value = '-';
                                }
                                break;
                            case 'notes': 
                                if (!empty($labelDetails['notes'])) {
                                    $value = substr(htmlspecialchars($labelDetails['notes']), 0, 20); 
                                    if (strlen($labelDetails['notes']) > 20) $value .= '...';
                                }
                                break;
                        }
                        
                        if (empty(trim((string)$value)) && !in_array($fieldKey, ['item_name', 'notes', 'label_title', 'used_at', 'validated_at', 'validator_username'])) continue; 
                        if ($fieldKey === 'item_name' && empty(trim((string)$value))) continue;
                        if ($fieldKey === 'label_title' && empty(trim((string)$value))) { if ($fieldConfig['visible']) { continue; } }
                        if ($fieldKey === 'notes' && empty(trim((string)$value))) continue;
                        if (in_array($fieldKey, ['used_at', 'validated_at', 'validator_username']) && $value === '-') {
                             if (!empty(trim((string)($fieldConfig['custom_label'] ?? '')))) { /* tampilkan label jika ada custom */ }
                             else if (!($fieldConfig['hide_label'] ?? false) && !empty($fieldConfig['label'])) { /* tampilkan label jika ada default & tidak hide */ }
                             else { continue; } 
                        }

                        if ($isItemName): ?>
                            <div class="item-name-value-thermal"><?php echo htmlspecialchars($value); ?></div>
                        <?php else: ?>
                            <div class="label-thermal-section">
                                <?php if (!$hideThisLabel): ?>
                                    <strong><?php echo $currentFieldLabelText; ?>:</strong> 
                                <?php endif; ?>
                                <span class="value"><?php echo htmlspecialchars($value); ?></span>
                            </div>
                        <?php endif; 
                    endforeach;
                    ?>
                </div>

                <div class="qr-code-container-thermal">
                    <?php 
                    if (!$qrLibMissing && class_exists('QRcode') && isset($labelDetails['label_unique_id']) && !empty($labelDetails['label_unique_id'])) {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) &&$_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); 
                        if ($basePath === '/' || $basePath === '\\') $basePath = ''; 
                        $qrData = $protocol . $host . $basePath . "/handle_qr_scan.php?uid=" . urlencode($labelDetails['label_unique_id']);
                        ob_start();
                        QRcode::png($qrData, null, QR_ECLEVEL_L, $qrPixelSize, 1);
                        $imageData = ob_get_contents();
                        ob_end_clean();
                        echo '<img src="data:image/png;base64,' . base64_encode($imageData) . '" alt="QR Code">';
                    } elseif ($qrLibMissing) { 
                        echo '<p style="color:red;font-size:5pt;"><em>QR Lib missing</em></p>';
                    } else { 
                        echo '<p style="color:red;font-size:5pt;"><em>QR Error</em></p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="no-print-on-labelpage" style="padding:20px; text-align:center; color:red;">
            <h1>Error</h1>
            <p><?php echo htmlspecialchars($errorMessage); ?></p>
            <button onclick="window.close();">Tutup</button>
        </div>
    <?php endif; ?>
</body>
</html>