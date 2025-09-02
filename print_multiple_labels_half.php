<?php
/**
 * Print Multiple Labels Page (Half Layout - Top Aligned)
 *
 * This version uses a sophisticated vertical watermark on the side for reprints,
 * on a half-page layout with content aligned to the top.
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

// Logika PHP sama persis dengan print_multiple_labels.php
require_once 'config.php';
require_once __DIR__ . '/libs/phpqrcode/qrlib.php';
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

// Duplikasi struktur default dari config.php untuk keamanan
$defaultThermalFieldsStructure = [ 
    'item_name'         => ['visible' => true,  'order' => 1, 'label' => 'Nama Item', 'hide_label' => false, 'custom_label' => ''],
    'label_title'       => ['visible' => true,  'order' => 2, 'label' => 'Judul Label', 'hide_label' => false, 'custom_label' => ''],
    'label_unique_id'   => ['visible' => false, 'order' => 3, 'label' => 'ID Label Unik', 'hide_label' => false, 'custom_label' => ''],
    'created_at'        => ['visible' => true,  'order' => 4, 'label' => 'Tanggal Buat', 'hide_label' => false, 'custom_label' => ''],
    'expiry_date'       => ['visible' => true,  'order' => 5, 'label' => 'Tanggal Kedaluwarsa', 'hide_label' => false, 'custom_label' => ''],
    'load_name'         => ['visible' => false, 'order' => 6, 'label' => 'Nama Muatan', 'hide_label' => false, 'custom_label' => ''],
    'cycle_number'      => ['visible' => false, 'order' => 7, 'label' => 'Nomor Siklus', 'hide_label' => false, 'custom_label' => ''],
    'machine_name'      => ['visible' => false, 'order' => 8, 'label' => 'Nama Mesin', 'hide_label' => false, 'custom_label' => ''],
    'cycle_operator_name' => ['visible' => false, 'order' => 9, 'label' => 'Operator Siklus', 'hide_label' => false, 'custom_label' => ''],
    'cycle_date'        => ['visible' => false, 'order' => 10, 'label' => 'Tanggal Siklus', 'hide_label' => false, 'custom_label' => ''],
    'load_creator_name'   => ['visible' => false, 'order' => 11, 'label' => 'Pembuat Muatan', 'hide_label' => false, 'custom_label' => ''],
    'destination_department_name' => ['visible' => false, 'order' => 12, 'label' => 'Departemen Tujuan', 'hide_label' => false, 'custom_label' => ''],
    'creator_username'  => ['visible' => false, 'order' => 13, 'label' => 'Dibuat Oleh (Label)', 'hide_label' => false, 'custom_label' => ''],
    'used_at'           => ['visible' => false, 'order' => 14, 'label' => 'Tanggal Digunakan', 'hide_label' => false, 'custom_label' => ''],
    'notes'             => ['visible' => false, 'order' => 15, 'label' => 'Catatan Tambahan', 'hide_label' => false, 'custom_label' => ''],
    'custom_text_1'     => ['visible' => false, 'order' => 16, 'label' => 'Teks Kustom 1', 'hide_label' => true,  'custom_label' => ''],
    'custom_text_2'     => ['visible' => false, 'order' => 17, 'label' => 'Teks Kustom 2', 'hide_label' => true,  'custom_label' => '']
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

$qrPixelSize = 6;

$labelsToPrint = [];
$conn = connectToDatabase();
if ($conn) {
    $sql = "SELECT 
                sr.record_id, sr.label_unique_id, sr.item_id, sr.item_type, sr.label_title, sr.created_at, 
                sr.expiry_date, sr.status, sr.notes, sr.used_at, sr.print_count, 
                u.username as creator_username,
                sl.load_name,
                sl_creator.full_name as load_creator_name,
                sc.cycle_number, sc.machine_name, sc.cycle_date,
                sc_operator.full_name as cycle_operator_name,
                dept.department_name as destination_department_name
            FROM sterilization_records sr
            LEFT JOIN users u ON sr.created_by_user_id = u.user_id
            LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
            LEFT JOIN users sl_creator ON sl.created_by_user_id = sl_creator.user_id
            LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
            LEFT JOIN users sc_operator ON sc.operator_user_id = sc_operator.user_id
            LEFT JOIN departments dept ON sr.destination_department_id = dept.department_id
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
        .label-print-container-thermal:last-child { page-break-after: auto; }
        .no-print { display: block; padding: 20px; text-align: center; background: #fff1f0; color: #cf1322;}
        .reprint-watermark-vertical {
            position: absolute; top: 2mm; right: 0.5mm; writing-mode: vertical-rl;
            transform: rotate(180deg); font-size: 6pt; font-weight: bold;
            color: rgba(0, 0, 0, 0.4); text-transform: uppercase;
            letter-spacing: 0.5px; pointer-events: none;
        }
        @media print { body { background-color: #fff; } .no-print { display: none; } }
    </style>
</head>
<body onload="window.print(); setTimeout(window.close, 1000);">
    <?php if (empty($labelsToPrint)): ?>
        <div class="no-print">Tidak ada data label yang ditemukan untuk dicetak. Halaman ini akan tertutup secara otomatis.</div>
    <?php else: ?>
        <?php foreach ($labelsToPrint as $label): ?>
            <div class="label-print-container-thermal" style="position: relative; width: <?php echo $thermalPaperWidthMM; ?>mm; height: <?php echo $thermalPaperHeightMM; ?>mm; padding: 0; box-sizing: border-box; display: flex; overflow: hidden; background-color: #fff !important; margin: 0; page-break-after: always;">
                 <div style="width: 50%; height: 100%; padding: <?php echo $labelInternalPadding; ?>; box-sizing: border-box; display: flex; flex-direction: column; font-size: 6pt; line-height: 1.2; overflow: hidden;">
                    <?php if ($label['print_count'] > 1): ?>
                        <div class="reprint-watermark-vertical">Copy #<?php echo ($label['print_count'] - 1); ?></div>
                    <?php endif; ?>
                    
                    <?php
                        $hasCustomText1 = isset($thermalFieldsConfig['custom_text_1']['visible']) && $thermalFieldsConfig['custom_text_1']['visible'] && !empty(trim($thermalCustomText1Value));
                        $hasCustomText2 = isset($thermalFieldsConfig['custom_text_2']['visible']) && $thermalFieldsConfig['custom_text_2']['visible'] && !empty(trim($thermalCustomText2Value));
                    ?>
                    <?php if ($hasCustomText1 || $hasCustomText2): ?>
                    <div style="text-align: center; margin-bottom: 0.5mm; flex-shrink: 0;">
                        <?php if ($hasCustomText1): ?>
                            <div style="font-size: 7pt; font-weight: bold; line-height: 1.1;"><?php echo htmlspecialchars($thermalCustomText1Value); ?></div>
                        <?php endif; ?>
                         <?php if ($hasCustomText2): ?>
                            <div style="font-size: 7pt; font-weight: bold; line-height: 1.1;"><?php echo htmlspecialchars($thermalCustomText2Value); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($fieldsToDisplay)): ?>
                        <div style="width: 90%; height: 0.5px; background-color: #6b7280; margin: 0.5mm auto; flex-shrink: 0;"></div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <div style="display: flex; flex-direction: column; width: 100%; overflow: hidden;">
                         <div style="text-align: center; margin-bottom: 1mm; width: 100%; flex-shrink: 0;">
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
                                echo '<img src="data:image/png;base64,' . base64_encode($imageData) . '" alt="QR Code" style="max-width: 100%; max-height: calc('.($thermalPaperHeightMM/2).'mm); height: auto; object-fit: contain; display: block; margin: 0 auto;">';
                            } elseif ($qrLibMissing) { echo '<p style="color:red;font-size:5pt;">QR Lib missing</p>';} 
                            else { echo '<p style="color:red;font-size:5pt;">QR Error</p>';}
                            ?>
                        </div>
                        <div style="overflow: hidden; display: flex; flex-direction: column; justify-content: flex-start; text-align: left; flex-shrink: 0;">
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
                                    case 'load_name': $value = $label['load_name'] ?? 'N/A'; break;
                                    case 'load_creator_name': $value = $label['load_creator_name'] ?? 'N/A'; break;
                                    case 'cycle_number': $value = $label['cycle_number'] ?? 'N/A'; break;
                                    case 'machine_name': $value = $label['machine_name'] ?? 'N/A'; break;
                                    case 'cycle_operator_name': $value = $label['cycle_operator_name'] ?? 'N/A'; break;
                                    case 'cycle_date': $value = isset($label['cycle_date']) ? (new DateTime($label['cycle_date']))->format('d/m/y H:i') : 'N/A'; break;
                                    case 'destination_department_name': $value = $label['destination_department_name'] ?? 'Stok Umum'; break;
                                    case 'used_at': if (isset($label['status']) && strtolower($label['status']) === 'used' && !empty($label['used_at'])) { $value = (new DateTime($label['used_at']))->format('d/m/y H:i'); } else { if (!($fieldConfig['visible'] ?? false)) continue 3; $value = '-'; } break;
                                    case 'notes': if (!empty($label['notes'])) { $value = substr(htmlspecialchars($label['notes']), 0, 20) . '...'; } break;
                                }
                                
                                if (empty(trim((string)$value)) || $value === '-') continue;

                                if ($isItemName): ?>
                                    <div style="font-size: 7pt; font-weight: bold; margin-bottom: 1mm; line-height: 1.1; display: block; text-align: left;"><?php echo htmlspecialchars($value); ?></div>
                                <?php else: ?>
                                    <div style="margin-bottom: 0.5mm; display: flex; justify-content: flex-start; align-items: baseline;">
                                        <?php if (!$hideThisLabel): ?>
                                            <strong style="margin-right: 1mm; flex-shrink: 0;"><?php echo $currentFieldLabelText; ?>:</strong> 
                                        <?php endif; ?>
                                        <span style="word-break: break-all; text-align: left;"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                <?php endif; 
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>
                <div style="position: absolute; left: 50%; top: 0; bottom: 0; border-left: 1px dashed #9ca3af;"></div>
                <div style="width: 50%; height: 100%; background-color: transparent;"></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>