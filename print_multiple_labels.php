<?php
/**
 * Print Multiple Labels Page (with Elegant Vertical Watermark)
 *
 * This version uses a sophisticated vertical watermark on the side for reprints,
 * ensuring no interference with the QR code or label content.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die("Akses ditolak. Silakan login.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Akses tidak sah atau token CSRF tidak valid.");
}

$recordIds = $_POST['record_ids'] ?? [];
if (empty($recordIds) || !is_array($recordIds)) {
    die("Tidak ada label yang dipilih untuk dicetak.");
}

$sanitizedRecordIds = array_map('intval', $recordIds);
$placeholders = implode(',', array_fill(0, count($sanitizedRecordIds), '?'));
$types = str_repeat('i', count($sanitizedRecordIds));

// --- Pengaturan Template ---
$thermalFieldsConfigFromDB = $app_settings['thermal_fields_config'] ?? [];
$thermalQrPosition = $app_settings['thermal_qr_position'] ?? 'bottom_center'; 
$thermalQrSizeSetting = $app_settings['thermal_qr_size'] ?? 'medium';
$thermalCustomText1Value = $app_settings['thermal_custom_text_1'] ?? '';
$thermalCustomText2Value = $app_settings['thermal_custom_text_2'] ?? '';
$thermalPaperWidthMM = (int)($app_settings['thermal_paper_width_mm'] ?? 70); 
$thermalPaperHeightMM = (int)($app_settings['thermal_paper_height_mm'] ?? 70); 
$labelInternalPadding = '2mm'; 

// ... (logika field config dan layout tetap sama)
$defaultThermalFieldsStructure = [ 
    'item_name' => ['visible' => true, 'order' => 1, 'label' => 'Nama Item', 'hide_label' => false, 'custom_label' => ''],
    'label_title' => ['visible' => true, 'order' => 2, 'label' => 'Nama Label', 'hide_label' => false, 'custom_label' => ''],
    'label_unique_id' => ['visible' => false, 'order' => 3, 'label' => 'ID Label Unik', 'hide_label' => false, 'custom_label' => ''],
    'created_at' => ['visible' => true, 'order' => 4, 'label' => 'Tanggal Buat', 'hide_label' => false, 'custom_label' => ''],
    'expiry_date' => ['visible' => true, 'order' => 5, 'label' => 'Tanggal Kedaluwarsa', 'hide_label' => false, 'custom_label' => ''],
    'used_at' => ['visible' => false, 'order' => 6, 'label' => 'Tanggal Digunakan',  'hide_label' => false, 'custom_label' => ''],
    'validated_at'      => ['visible' => false, 'order' => 7, 'label' => 'Tanggal Divalidasi', 'hide_label' => false, 'custom_label' => ''],
    'validator_username'=> ['visible' => false, 'order' => 8, 'label' => 'Divalidasi Oleh',    'hide_label' => false, 'custom_label' => ''],
    'creator_username'  => ['visible' => false, 'order' => 9, 'label' => 'Dibuat Oleh',        'hide_label' => false, 'custom_label' => ''],
    'notes'             => ['visible' => false, 'order' => 10, 'label' => 'Catatan Tambahan',   'hide_label' => false, 'custom_label' => ''],
    'custom_text_1'     => ['visible' => false, 'order' => 11, 'label' => 'Info Kustom 1',      'hide_label' => true,  'custom_label' => ''], 
    'custom_text_2'     => ['visible' => false, 'order' => 12, 'label' => 'Info Kustom 2',     'hide_label' => true,  'custom_label' => '']  
];
$thermalFieldsConfig = [];
foreach ($defaultThermalFieldsStructure as $key => $defaultValues) {
    $loadedConfig = $thermalFieldsConfigFromDB[$key] ?? [];
    $thermalFieldsConfig[$key] = [
        'visible'    => (bool)($loadedConfig['visible'] ?? $defaultValues['visible']),
        'order'      => (int)($loadedConfig['order'] ?? $defaultValues['order']),
        'label'      => $defaultValues['label'],
        'hide_label' => (bool)($loadedConfig['hide_label'] ?? $defaultValues['hide_label']),
        'custom_label' => (string)($loadedConfig['custom_label'] ?? $defaultValues['custom_label']) 
    ];
}
$fieldsToDisplay = array_filter($thermalFieldsConfig, function($field) {
    return $field['visible'] ?? false;
});
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

// --- Mengambil semua data label yang diperlukan dalam satu query ---
$labelsToPrint = [];
$conn = connectToDatabase();
if ($conn) {
    $sql = "SELECT sr.record_id, sr.label_unique_id, sr.item_id, sr.item_type, sr.label_title, sr.created_at, sr.expiry_date, sr.status, sr.notes, sr.used_at, sr.print_count, u.username as creator_username
            FROM sterilization_records sr
            LEFT JOIN users u ON sr.created_by_user_id = u.user_id
            WHERE sr.record_id IN ($placeholders)
            ORDER BY sr.label_title ASC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$sanitizedRecordIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $labelsToPrint[$row['record_id']] = $row;
        }
        $stmt->close();
    }
    // Mengambil nama item secara terpisah untuk efisiensi
    $itemIds = array_column($labelsToPrint, 'item_id', 'record_id');
    $itemTypes = array_column($labelsToPrint, 'item_type', 'record_id');
    $instrumentIdsToFetch = [];
    $setIdsToFetch = [];
    foreach($itemTypes as $record_id => $type) {
        if ($type === 'instrument') $instrumentIdsToFetch[] = $itemIds[$record_id];
        if ($type === 'set') $setIdsToFetch[] = $itemIds[$record_id];
    }
    
    $itemNames = [];
    if (!empty($instrumentIdsToFetch)) {
        $instPlaceholders = implode(',', array_fill(0, count($instrumentIdsToFetch), '?'));
        $stmtInst = $conn->prepare("SELECT instrument_id, instrument_name FROM instruments WHERE instrument_id IN ($instPlaceholders)");
        $stmtInst->bind_param(str_repeat('i', count($instrumentIdsToFetch)), ...$instrumentIdsToFetch);
        $stmtInst->execute();
        $resInst = $stmtInst->get_result();
        while($row = $resInst->fetch_assoc()) $itemNames['instrument'][$row['instrument_id']] = $row['instrument_name'];
        $stmtInst->close();
    }
    if (!empty($setIdsToFetch)) {
        $setPlaceholders = implode(',', array_fill(0, count($setIdsToFetch), '?'));
        $stmtSet = $conn->prepare("SELECT set_id, set_name FROM instrument_sets WHERE set_id IN ($setPlaceholders)");
        $stmtSet->bind_param(str_repeat('i', count($setIdsToFetch)), ...$setIdsToFetch);
        $stmtSet->execute();
        $resSet = $stmtSet->get_result();
        while($row = $resSet->fetch_assoc()) $itemNames['set'][$row['set_id']] = $row['set_name'];
        $stmtSet->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Massal</title>
    <style>
        @page { size: <?php echo $thermalPaperWidthMM; ?>mm <?php echo $thermalPaperHeightMM; ?>mm; margin: 0mm; }
        body { margin: 0; padding: 0; background-color: #e0e0e0; font-family: 'Arial', sans-serif; }
        .label-print-container-thermal {
            position: relative; /* Diperlukan untuk watermark */
            width: <?php echo $thermalPaperWidthMM; ?>mm; 
            height: <?php echo $thermalPaperHeightMM; ?>mm; 
            padding: <?php echo $labelInternalPadding; ?>; 
            box-sizing: border-box; 
            font-size: 12pt; font-weight: bold; line-height: 1.15; 
            display: flex; flex-direction: column; overflow: hidden; 
            background-color: #fff !important; margin: 0; 
            page-break-after: always;
        }
        .label-print-container-thermal:last-child { page-break-after: auto; }
        .top-custom-text-area { width: 100%; text-align: center; margin-bottom: 1mm; flex-shrink: 0; }
        .custom-text-line-print { font-size: 12pt; font-weight: bold; line-height: 1.15; margin-bottom: 0.5mm; }
        .separator-line { width: 85%; height: 0.5px; background-color: #333; margin: 1mm auto 1.5mm auto; flex-shrink: 0; }
        .main-label-area { display: flex; flex-direction: <?php echo $mainAreaFlexDirection; ?> !important; flex-grow: 1; width: 100%; overflow: hidden; }
        .label-thermal-content { overflow: hidden; display: flex; flex-direction: column; justify-content: center; order: <?php echo $contentAreaFlexOrder; ?> !important; text-align: <?php echo $contentBlockTextAlign; ?>;
            <?php if ($mainAreaFlexDirection === 'row'): ?> flex-grow: 1; padding-left: <?php echo ($thermalQrPosition === 'middle_left') ? $gapBetweenQrAndText : '0'; ?>; padding-right: <?php echo ($thermalQrPosition === 'middle_right') ? $gapBetweenQrAndText : '0'; ?>; max-width: <?php echo $contentMaxWidth; ?>; <?php else: ?> width: 100%; <?php endif; ?>
        }
        .item-name-value-thermal { font-size: 12pt; margin-bottom: 1.5mm; line-height: 1.1; display: block; text-align: <?php echo $contentBlockTextAlign; ?>; <?php if ($mainAreaFlexDirection === 'row'): ?> text-align: left; <?php endif; ?> }
        .label-thermal-section { margin-bottom: 1mm; display: flex; justify-content: <?php echo $sectionItemsJustify; ?>; flex-wrap: nowrap; align-items: baseline; }
        .label-thermal-section strong { margin-right: 1mm; flex-shrink: 0; min-width: auto; }
        .label-thermal-section span.value { word-break: break-all; text-align: left; }
        .qr-code-container-thermal { text-align: center; flex-shrink: 0; order: <?php echo $qrContainerFlexOrder; ?> !important; align-self: <?php echo $qrContainerAlignSelf; ?>;
            <?php if ($mainAreaFlexDirection === 'column'): ?> margin-top: auto; padding-top: 1mm; width: auto; max-width:100%; padding-bottom: <?php echo ($thermalQrPosition === 'top_center' || $thermalQrPosition === 'top_left_aligned' || $thermalQrPosition === 'top_right_aligned' ? '1mm' : '0'); ?>;
            <?php else: ?> display: flex; align-items: center; justify-content: center; width: <?php echo $qrImageSizeCSS; ?>; height: 100%; <?php endif; ?>
        }
        .qr-code-container-thermal img { max-width: <?php echo $qrImageSizeCSS; ?>; max-height:<?php echo ($mainAreaFlexDirection === 'row' ? 'calc('.$thermalPaperHeightMM.'mm - 2 * '.$labelInternalPadding.')' : $qrImageSizeCSS); ?>; height: auto; display: block; }
        .no-print { display: block; padding: 20px; text-align: center; background: #fff1f0; color: #cf1322;}
        
        /* PERUBAHAN: Gaya baru untuk watermark vertikal */
        .reprint-watermark-vertical {
            position: absolute;
            top: 2mm;
            right: 0.5mm;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: 6pt;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            pointer-events: none;
        }
        
        @media print {
            body { background-color: #fff; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(window.close, 1000);">
    <?php if (empty($labelsToPrint)): ?>
        <div class="no-print">Tidak ada data label yang ditemukan untuk dicetak. Halaman ini akan tertutup secara otomatis.</div>
    <?php else: ?>
        <?php foreach ($labelsToPrint as $label): ?>
            <div class="label-print-container-thermal">
                <?php if ($label['print_count'] > 1): ?>
                    <div class="reprint-watermark-vertical">Copy #<?php echo ($label['print_count'] - 1); ?></div>
                <?php endif; ?>
                
                <div class="top-custom-text-area">
                    <?php if (isset($thermalFieldsConfig['custom_text_1']['visible']) && $thermalFieldsConfig['custom_text_1']['visible'] && !empty(trim($thermalCustomText1Value))): ?>
                        <div class="custom-text-line-print"><?php echo htmlspecialchars($thermalCustomText1Value); ?></div>
                    <?php endif; ?>
                    <?php if (isset($thermalFieldsConfig['custom_text_2']['visible']) && $thermalFieldsConfig['custom_text_2']['visible'] && !empty(trim($thermalCustomText2Value))): ?>
                        <div class="custom-text-line-print"><?php echo htmlspecialchars($thermalCustomText2Value); ?></div>
                    <?php endif; ?>
                </div>
                <?php if ((isset($thermalFieldsConfig['custom_text_1']['visible']) && $thermalFieldsConfig['custom_text_1']['visible'] && !empty(trim($thermalCustomText1Value))) && !empty($fieldsToDisplay)): ?>
                    <div class="separator-line"></div>
                <?php endif; ?>

                <div class="main-label-area">
                    <div class="label-thermal-content">
                        <?php
                        foreach ($fieldsToDisplay as $fieldKey => $fieldConfig):
                            if (in_array($fieldKey, ['custom_text_1', 'custom_text_2'])) continue;
                            
                            $value = ''; $isItemName = ($fieldKey === 'item_name');
                            $currentFieldLabelText = !empty(trim((string)($fieldConfig['custom_label'] ?? ''))) ? htmlspecialchars($fieldConfig['custom_label']) : htmlspecialchars($fieldConfig['label']);
                            $hideThisLabel = (bool)($fieldConfig['hide_label'] ?? false);

                            switch ($fieldKey) {
                                case 'item_name': $value = $itemNames[$label['item_type']][$label['item_id']] ?? 'Item Tidak Dikenal'; break;
                                case 'label_unique_id': $value = $label['label_unique_id'] ?? 'N/A'; break;
                                case 'label_title': $value = $label['label_title'] ?? 'N/A'; break; 
                                case 'creator_username': $value = $label['creator_username'] ?? 'N/A'; break;
                                case 'created_at': $value = isset($label['created_at']) ? (new DateTime($label['created_at']))->format('d/m/y H:i') : 'N/A'; break;
                                case 'expiry_date': $value = isset($label['expiry_date']) ? (new DateTime($label['expiry_date']))->format('d/m/y H:i') : 'N/A'; break;
                                case 'used_at': if (isset($label['status']) && strtolower($label['status']) === 'used' && !empty($label['used_at'])) { $value = (new DateTime($label['used_at']))->format('d/m/y H:i'); } else { if (!($fieldConfig['visible'] ?? false)) continue 3; $value = '-'; } break;
                                case 'notes': if (!empty($label['notes'])) { $value = substr(htmlspecialchars($label['notes']), 0, 20) . '...'; } break;
                            }
                            
                            if (empty(trim((string)$value)) || $value === '-') continue;

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
                        if (!$qrLibMissing && !empty($label['label_unique_id'])) {
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
                            $host = $_SERVER['HTTP_HOST'];
                            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                            if ($basePath === '/' || $basePath === '\\') $basePath = '';
                            $qrData = $protocol . $host . $basePath . "/handle_qr_scan.php?uid=" . urlencode($label['label_unique_id']);
                            ob_start();
                            QRcode::png($qrData, null, QR_ECLEVEL_L, $qrPixelSize, 1);
                            $imageData = ob_get_contents();
                            ob_end_clean();
                            echo '<img src="data:image/png;base64,' . base64_encode($imageData) . '" alt="QR Code">';
                        } elseif ($qrLibMissing) { echo '<p style="color:red;font-size:5pt;">QR Lib missing</p>';} 
                        else { echo '<p style="color:red;font-size:5pt;">QR Error</p>';}
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>