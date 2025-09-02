<?php
/**
 * Application Settings Page
 *
 * Allows admins to configure application settings.
 * This version introduces relevant traceability fields replacing obsolete ones.
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

$pageTitle = "Pengaturan Aplikasi";
require_once 'header.php'; // Includes session check, CSRF token ($csrfToken), $app_settings etc.

// Ensure only admin can access this page
if ($userRole !== 'admin') {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman pengaturan.'];
    header("Location: index.php");
    exit;
}

// --- Data Fetching and Variable Initialization ---
$currentAppInstanceName = $app_settings['app_instance_name'] ?? 'Sterilabel'; 
$currentAppLogoFilename = $app_settings['app_logo_filename'] ?? '';
$currentShowAppNameBesideLogo = (bool)($app_settings['show_app_name_beside_logo'] ?? true);
$currentDefaultExpiryDays = $app_settings['default_expiry_days'] ?? '30'; 
$currentShowStatusBlockOnDetailPage = (bool)($app_settings['show_status_block_on_detail_page'] ?? true);
$currentThermalFieldsConfigLoaded = $app_settings['thermal_fields_config'] ?? []; 
$currentThermalQrPosition = $app_settings['thermal_qr_position'] ?? 'bottom_center';
$currentThermalQrSize = $app_settings['thermal_qr_size'] ?? 'medium';
$staffCanManageInstruments = (bool)($app_settings['staff_can_manage_instruments'] ?? false);
$staffCanManageSets = (bool)($app_settings['staff_can_manage_sets'] ?? false);
$staffCanViewActivityLog = (bool)($app_settings['staff_can_view_activity_log'] ?? false);
$currentThermalCustomText1 = $app_settings['thermal_custom_text_1'] ?? '';
$currentThermalCustomText2 = $app_settings['thermal_custom_text_2'] ?? '';
$currentThermalPaperWidth = $app_settings['thermal_paper_width_mm'] ?? '70'; 
$currentThermalPaperHeight = $app_settings['thermal_paper_height_mm'] ?? '40';
$currentPrintTemplate = $app_settings['print_template'] ?? 'normal';

// --- PERUBAHAN: Memperbarui struktur field dengan data yang lebih relevan ---
$defaultThermalFieldsStructure = [ 
    'item_name'         => ['visible' => true,  'order' => 1, 'label' => 'Nama Item',          'group' => 'Informasi Esensial', 'hide_label' => false, 'custom_label' => ''],
    'label_title'       => ['visible' => true,  'order' => 2, 'label' => 'Judul Label',        'group' => 'Informasi Esensial', 'hide_label' => false, 'custom_label' => ''],
    'created_at'        => ['visible' => true,  'order' => 3, 'label' => 'Tanggal Buat',       'group' => 'Informasi Esensial', 'hide_label' => false, 'custom_label' => ''],
    'expiry_date'       => ['visible' => true,  'order' => 4, 'label' => 'Tanggal Kedaluwarsa','group' => 'Informasi Esensial', 'hide_label' => false, 'custom_label' => ''],
    'label_unique_id'   => ['visible' => false, 'order' => 5, 'label' => 'ID Unik Label',      'group' => 'Informasi Esensial', 'hide_label' => false, 'custom_label' => ''],
    
    'load_name'         => ['visible' => false, 'order' => 6, 'label' => 'Nama Muatan',        'group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],
    'cycle_number'      => ['visible' => false, 'order' => 7, 'label' => 'Nomor Siklus',       'group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],
    'machine_name'      => ['visible' => false, 'order' => 8, 'label' => 'Nama Mesin',         'group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],
    'cycle_operator_name' => ['visible' => false, 'order' => 9, 'label' => 'Operator Siklus',  'group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],
    'cycle_date'        => ['visible' => false, 'order' => 10, 'label' => 'Tanggal Siklus',     'group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],
    'load_creator_name'   => ['visible' => false, 'order' => 11, 'label' => 'Pembuat Muatan',   'group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],
    'destination_department_name' => ['visible' => false, 'order' => 12, 'label' => 'Departemen Tujuan','group' => 'Informasi Pelacakan', 'hide_label' => false, 'custom_label' => ''],

    'creator_username'  => ['visible' => false, 'order' => 13, 'label' => 'Dibuat Oleh (Label)', 'group' => 'Informasi Tambahan', 'hide_label' => false, 'custom_label' => ''],
    'used_at'           => ['visible' => false, 'order' => 14, 'label' => 'Tanggal Digunakan', 'group' => 'Informasi Tambahan', 'hide_label' => false, 'custom_label' => ''],
    'notes'             => ['visible' => false, 'order' => 15, 'label' => 'Catatan Tambahan',  'group' => 'Informasi Tambahan', 'hide_label' => false, 'custom_label' => ''],
    
    'custom_text_1'     => ['visible' => false, 'order' => 16, 'label' => 'Teks Kustom 1',     'group' => 'Teks Kustom', 'hide_label' => true,  'custom_label' => ''],
    'custom_text_2'     => ['visible' => false, 'order' => 17, 'label' => 'Teks Kustom 2',     'group' => 'Teks Kustom', 'hide_label' => true,  'custom_label' => '']
];

$currentThermalFieldsConfig = [];
foreach ($defaultThermalFieldsStructure as $key => $defaultValues) {
    $loadedConfig = $currentThermalFieldsConfigLoaded[$key] ?? []; 
    $currentThermalFieldsConfig[$key] = [
        'visible'      => (bool)($loadedConfig['visible'] ?? $defaultValues['visible']),
        'order'        => (int)($loadedConfig['order'] ?? $defaultValues['order']),
        'label'        => $defaultValues['label'],
        'group'        => $defaultValues['group'],
        'hide_label'   => (bool)($loadedConfig['hide_label'] ?? $defaultValues['hide_label']),
        'custom_label' => (string)($loadedConfig['custom_label'] ?? $defaultValues['custom_label']) 
    ];
}

uasort($currentThermalFieldsConfig, function($a, $b) {
    $groupOrder = ['Informasi Esensial' => 1, 'Informasi Pelacakan' => 2, 'Informasi Tambahan' => 3, 'Teks Kustom' => 4];
    $groupComparison = ($groupOrder[$a['group']] ?? 99) <=> ($groupOrder[$b['group']] ?? 99);
    if ($groupComparison !== 0) {
        return $groupComparison;
    }
    return ($a['order'] ?? 999) <=> ($b['order'] ?? 999); 
});
$thermalFieldOptionsDisplay = $currentThermalFieldsConfig;

$qrPositionOptions = [
    'bottom_center' => 'Di Bawah Teks (Tengah)', 'top_center' => 'Di Atas Teks (Tengah)',
    'top_left_aligned' => 'Di Atas Teks (Rata Kiri)', 'top_right_aligned' => 'Di Atas Teks (Rata Kanan)', 
    'middle_left' => 'Di Samping Kiri Teks (Tengah Vertikal)', 'middle_right' => 'Di Samping Kanan Teks (Tengah Vertikal)',
];
$qrSizeOptions = [
    'small' => 'Kecil (sekitar 15-18mm)', 'medium' => 'Sedang (sekitar 18-22mm)', 'large' => 'Besar (sekitar 22-25mm)'
];

?>
<style>
    .current-logo-preview { max-width: 150px; max-height: 50px; object-fit: contain; background-color: #f3f4f6; border: 1px dashed #d1d5db; padding: 0.5rem; border-radius: 0.375rem; margin-top: 0.5rem; }
    .field-config-item { display: grid; grid-template-columns: auto minmax(100px, 1fr) minmax(100px, 1fr) auto auto; align-items: center; gap: 0.5rem; padding: 0.5rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; }
    .field-config-item:last-child { border-bottom: none; }
    .field-config-item .field-name { grid-column: 2 / 3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .field-config-item .hide-label-checkbox-container { grid-column: 4 / 5; display: flex; align-items: center; justify-content: center; }
    .field-config-item input[type="checkbox"].visibility-checkbox { grid-column: 1 / 2; margin-right: 0; height: 1.1rem; width: 1.1rem; color: #3b82f6; }
    .field-config-item input[type="checkbox"].hide-label-checkbox { margin-right: 0.1rem; height: 1rem; width: 1rem; }
    .field-config-header { display: grid; grid-template-columns: auto minmax(100px, 1fr) minmax(100px, 1fr) auto auto; gap: 0.5rem; font-weight: 600; font-size: 0.7rem; color: #6b7280; padding: 0 0.5rem 0.25rem 0.5rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 0.5rem; }
    .field-config-header > div { text-align: left; }
    .field-config-header .text-center { text-align: center; }
    .permission-item { display: flex; align-items: center; padding: 0.5rem 0; }
    .permission-item label span { flex-grow: 1; }
    .permission-item input[type="checkbox"] { margin-right: 0.75rem; height: 1.25rem; width: 1.25rem; color: #3b82f6; flex-shrink: 0; }
    .input-group-mm .form-input { border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .input-group-mm .input-group-append { padding: 0.5rem 0.75rem; background-color: #e9ecef; border: 1px solid #d1d5db; border-left: 0; border-top-right-radius: 0.25rem; border-bottom-right-radius: 0.25rem; font-size:0.875rem; color: #4b5563;}
    .field-group-header {
        font-size: 0.75rem; font-weight: 700;
        color: #4b5563; background-color: #f3f4f6;
        padding: 0.375rem 0.75rem; margin-top: 1rem;
        border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;
    }
    .field-group-header:first-of-type { margin-top: 0; border-top: none; }
    
    /* --- Gaya untuk Pratinjau --- */
    #preview-container-wrapper {
        padding: 1rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }
    #thermal-preview-container {
        border: 2px dashed #a5b4fc;
        background-color: white;
        margin: 0 auto;
        transform-origin: top center;
        transition: width 0.3s ease, height 0.3s ease;
        overflow: hidden; /* Penting */
        font-family: Arial, sans-serif;
        font-weight: bold;
        color: #333;
    }
    .preview-qr-placeholder {
        background-color: #e5e7eb;
        border: 1px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 0.3s ease, height 0.3s ease;
    }
    .preview-qr-placeholder .material-icons {
        font-size: 48px;
        color: #9ca3af;
    }
</style>

<main class="container mx-auto px-4 sm:px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Pengaturan Aplikasi</h2>
        <a href="index.php" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Kembali ke Dashboard</a>
    </div>

    <form action="php_scripts/settings_update.php" method="POST" id="settingsForm" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <div class="space-y-8">
                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b">Pengaturan Umum & Tampilan</h3>
                    <div class="mb-6 pb-6 border-b">
                        <label for="app_instance_name" class="form-label text-lg">Nama Aplikasi/Instansi</label>
                        <input type="text" name="app_instance_name" id="app_instance_name" class="form-input" value="<?php echo htmlspecialchars($currentAppInstanceName); ?>" placeholder="Contoh: RS Sehat Selalu">
                    </div>
                    <div class="mb-6 pb-6 border-b">
                        <label for="app_logo" class="form-label text-lg">Logo Aplikasi</label>
                        <?php if (!empty($currentAppLogoFilename) && file_exists('uploads/' . $currentAppLogoFilename)): ?>
                            <div class="mb-3"><p class="text-sm font-medium text-gray-700">Logo saat ini:</p><img src="uploads/<?php echo htmlspecialchars($currentAppLogoFilename) . '?t=' . time(); ?>" alt="Logo Saat Ini" class="current-logo-preview"></div>
                        <?php endif; ?>
                        <input type="file" name="app_logo" id="app_logo" class="form-input" accept="image/png, image/jpeg, image/svg+xml">
                        <div class="permission-item mt-3"><label class="flex items-center"><input type="checkbox" name="show_app_name_beside_logo" value="1" class="form-checkbox" <?php echo $currentShowAppNameBesideLogo ? 'checked' : ''; ?>><span class="ml-2 text-sm">Tampilkan Nama di samping logo</span></label></div>
                        <?php if (!empty($currentAppLogoFilename)): ?>
                            <div class="mt-2 flex items-center"><input type="checkbox" name="delete_logo" id="deleteLogo" value="1" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500"><label for="deleteLogo" class="ml-2 text-sm text-red-600">Hapus logo saat ini</label></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-6 pb-6 border-b"> 
                        <label for="default_expiry_days" class="form-label text-lg">Durasi Kedaluwarsa Default</label>
                        <div class="flex items-center"><input type="number" name="default_expiry_days" id="default_expiry_days" class="form-input w-24 mr-2" value="<?php echo htmlspecialchars($currentDefaultExpiryDays); ?>" min="1" max="3650" required><span class="text-gray-600">hari</span></div>
                    </div>
                    <div>
                        <h4 class="form-label text-lg">Tampilan Publik</h4>
                        <div class="permission-item"><label class="flex items-center"><input type="checkbox" name="show_status_block_on_detail_page" value="1" class="form-checkbox" <?php echo $currentShowStatusBlockOnDetailPage ? 'checked' : ''; ?>><span class="ml-2 text-sm">Tampilkan Blok Status di Halaman Detail Publik</span></label></div>
                    </div>
                </div>
                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b">Pengguna & Hak Akses</h3>
                    <div>
                        <h4 class="form-label text-lg">Hak Akses Role "Staff"</h4>
                        <p class="text-sm text-gray-500 mb-3">Berikan izin kepada staff untuk mengakses modul tertentu.</p>
                        <div class="space-y-2">
                            <div class="permission-item"><label class="flex items-center"><input type="checkbox" name="staff_can_manage_instruments" value="1" class="form-checkbox" <?php echo $staffCanManageInstruments ? 'checked' : ''; ?>><span class="ml-2">Izinkan Staff Kelola Instrumen</span></label></div>
                            <div class="permission-item"><label class="flex items-center"><input type="checkbox" name="staff_can_manage_sets" value="1" class="form-checkbox" <?php echo $staffCanManageSets ? 'checked' : ''; ?>><span class="ml-2">Izinkan Staff Kelola Set</span></label></div>
                            <div class="permission-item"><label class="flex items-center"><input type="checkbox" name="staff_can_view_activity_log" value="1" class="form-checkbox" <?php echo $staffCanViewActivityLog ? 'checked' : ''; ?>><span class="ml-2">Izinkan Staff Melihat Log Aktivitas</span></label></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="card" id="thermal-settings-card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b">Pengaturan Template Thermal</h3>
                    
                    <div id="preview-container-wrapper" class="mb-6">
                        <h4 class="form-label text-center text-sm mb-2">Pratinjau Label Real-time</h4>
                        <div id="thermal-preview-container">
                            </div>
                    </div>
                    
                    <div class="mb-6 pb-6 border-b">
                        <label for="print_template" class="form-label text-lg">Template Cetak</label>
                        <select name="print_template" id="print_template" class="form-select">
                            <option value="normal" <?php echo ($currentPrintTemplate === 'normal') ? 'selected' : ''; ?>>Normal (Penuh)</option>
                            <option value="half" <?php echo ($currentPrintTemplate === 'half') ? 'selected' : ''; ?>>Half (Separuh Kertas)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih tata letak default untuk pencetakan label.</p>
                    </div>
                    <div class="mb-6 pb-6 border-b"> 
                        <h4 class="form-label text-lg">Kustomisasi Field</h4>
                        <div class="field-config-header mt-3"><div>Tampil</div><div>Nama Field</div><div>Label Kustom</div><div class="text-center">Sembunyi Label</div><div class="text-center">Urutan</div></div>
                        <div class="clear-both"></div>
                        <div class="max-h-80 overflow-y-auto">
                            <?php 
                            $currentGroup = null;
                            foreach ($thermalFieldOptionsDisplay as $fieldKey => $fieldConfig):
                                if ($fieldConfig['group'] !== $currentGroup) {
                                    $currentGroup = $fieldConfig['group'];
                                    echo '<div class="field-group-header">' . htmlspecialchars($currentGroup) . '</div>';
                                }
                            ?>
                                <div class="field-config-item" data-field-key="<?php echo $fieldKey; ?>">
                                    <input type="checkbox" name="thermal_fields_config[<?php echo $fieldKey; ?>][visible]" value="1" class="visibility-checkbox" <?php echo ($fieldConfig['visible']) ? 'checked' : ''; ?>>
                                    <span class="field-name" title="<?php echo htmlspecialchars($fieldConfig['label']); ?>"><?php echo htmlspecialchars($fieldConfig['label']); ?></span>
                                    <input type="text" name="thermal_fields_config[<?php echo $fieldKey; ?>][custom_label]" class="form-input form-input-sm" value="<?php echo htmlspecialchars($fieldConfig['custom_label'] ?? ''); ?>" placeholder="Default">
                                    <div class="hide-label-checkbox-container"><input type="checkbox" name="thermal_fields_config[<?php echo $fieldKey; ?>][hide_label]" value="1" class="hide-label-checkbox" <?php echo ($fieldConfig['hide_label']) ? 'checked' : ''; ?>></div>
                                    <input type="number" name="thermal_fields_config[<?php echo $fieldKey; ?>][order]" min="1" max="<?php echo count($defaultThermalFieldsStructure); ?>" value="<?php echo htmlspecialchars((string)($fieldConfig['order'] ?? 999)); ?>" class="form-input form-input-sm w-14 text-center">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-6 pb-6 border-b"> 
                        <h4 class="form-label text-lg">Teks Kustom</h4>
                        <div class="space-y-4">
                            <div><label for="thermal_custom_text_1" class="form-label">Teks Kustom 1</label><input type="text" name="thermal_custom_text_1" id="thermal_custom_text_1" class="form-input" value="<?php echo htmlspecialchars($currentThermalCustomText1); ?>"></div>
                            <div><label for="thermal_custom_text_2" class="form-label">Teks Kustom 2</label><input type="text" name="thermal_custom_text_2" id="thermal_custom_text_2" class="form-input" value="<?php echo htmlspecialchars($currentThermalCustomText2); ?>"></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="form-label text-lg">Tata Letak & Ukuran</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4 mt-3">
                            <div><label for="thermal_qr_position" class="form-label">Posisi QR Code</label><select name="thermal_qr_position" id="thermal_qr_position" class="form-select"><?php foreach ($qrPositionOptions as $posKey => $posLabel): ?><option value="<?php echo $posKey; ?>" <?php echo ($currentThermalQrPosition === $posKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($posLabel); ?></option><?php endforeach; ?></select></div>
                            <div><label for="thermal_qr_size" class="form-label">Ukuran QR Code</label><select name="thermal_qr_size" id="thermal_qr_size" class="form-select"><?php foreach ($qrSizeOptions as $sizeKey => $sizeLabel): ?><option value="<?php echo $sizeKey; ?>" <?php echo ($currentThermalQrSize === $sizeKey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sizeLabel); ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div>
                            <h5 class="form-label mb-1">Ukuran Kertas (mm)</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div><label for="thermal_paper_width_mm" class="form-label text-sm">Lebar</label><div class="flex input-group-mm"><input type="number" name="thermal_paper_width_mm" id="thermal_paper_width_mm" class="form-input" value="<?php echo htmlspecialchars($currentThermalPaperWidth); ?>" required><span class="input-group-append">mm</span></div></div>
                                <div><label for="thermal_paper_height_mm" class="form-label text-sm">Tinggi</label><div class="flex input-group-mm"><input type="number" name="thermal_paper_height_mm" id="thermal_paper_height_mm" class="form-input" value="<?php echo htmlspecialchars($currentThermalPaperHeight); ?>" required><span class="input-group-append">mm</span></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right mt-8"> 
            <button type="submit" class="btn btn-primary btn-lg py-3 px-6">
                <span class="material-icons mr-2">save</span>Simpan Semua Pengaturan
            </button>
        </div>
    </form>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const settingsForm = document.getElementById('settingsForm');
    const previewContainer = document.getElementById('thermal-preview-container');

    function getPreviewSettings() {
        const settings = {
            template: document.getElementById('print_template').value,
            width: document.getElementById('thermal_paper_width_mm').value,
            height: document.getElementById('thermal_paper_height_mm').value,
            qrPosition: document.getElementById('thermal_qr_position').value,
            qrSize: document.getElementById('thermal_qr_size').value,
            customText1: document.getElementById('thermal_custom_text_1').value,
            customText2: document.getElementById('thermal_custom_text_2').value,
            fields: []
        };
        
        document.querySelectorAll('.field-config-item').forEach(item => {
            const key = item.dataset.fieldKey;
            settings.fields.push({
                key: key,
                label: item.querySelector('.field-name').textContent,
                visible: item.querySelector('.visibility-checkbox').checked,
                order: parseInt(item.querySelector('input[type="number"]').value, 10),
                hideLabel: item.querySelector('.hide-label-checkbox').checked,
                customLabel: item.querySelector('input[type="text"]').value
            });
        });
        settings.fields.sort((a, b) => a.order - b.order);
        return settings;
    }

    function updatePreview() {
        const settings = getPreviewSettings();
        const previewWrapper = document.getElementById('preview-container-wrapper');
        const availableWidth = previewWrapper.clientWidth - 32;
        const scale = availableWidth < settings.width ? availableWidth / settings.width : 1;
        
        previewContainer.style.width = `${settings.width}mm`;
        previewContainer.style.height = `${settings.height}mm`;
        previewContainer.style.transform = `scale(${scale})`;
        previewContainer.style.fontSize = settings.template === 'half' ? '6pt' : '10pt';

        // Logika untuk konten tetap sama
        let mainFlexDirection = 'column';
        let qrOrder = 1, contentOrder = 0;
        let qrAlignSelf = 'center', contentTextAlign = 'center', sectionJustify = 'center';
        if (settings.qrPosition.startsWith('top')) { qrOrder = 0; contentOrder = 1; }
        if (settings.qrPosition.includes('left')) { qrAlignSelf = 'flex-start'; contentTextAlign = 'left'; sectionJustify = 'flex-start'; }
        if (settings.qrPosition.includes('right')) { qrAlignSelf = 'flex-end'; contentTextAlign = 'right'; sectionJustify = 'flex-end'; }
        if (settings.qrPosition.startsWith('middle')) { mainFlexDirection = 'row'; }
        if (settings.qrPosition === 'middle_left') { qrOrder = 0; contentOrder = 1; contentTextAlign = 'left'; sectionJustify = 'flex-start';}
        if (settings.qrPosition === 'middle_right') { qrOrder = 1; contentOrder = 0; contentTextAlign = 'left'; sectionJustify = 'flex-start';}
        
        const qrSizeMap = { small: '25mm', medium: '30mm', large: '35mm' };
        const qrSize = qrSizeMap[settings.qrSize] || '30mm';
        
        let customTextHtml = '';
        if (settings.fields.find(f => f.key === 'custom_text_1')?.visible && settings.customText1) { customTextHtml += `<div style="font-size: ${settings.template === 'half' ? '7pt' : '12pt'}; line-height: 1.1; font-weight: bold;">${settings.customText1}</div>`; }
        if (settings.fields.find(f => f.key === 'custom_text_2')?.visible && settings.customText2) { customTextHtml += `<div style="font-size: ${settings.template === 'half' ? '7pt' : '12pt'}; line-height: 1.1; font-weight: bold;">${settings.customText2}</div>`; }

        let fieldsHtml = '';
        settings.fields.forEach(field => {
            if (!field.visible || ['custom_text_1', 'custom_text_2'].includes(field.key)) return;
            const labelText = field.customLabel || field.label;
            const valueText = { item_name: "Nama Item Contoh", label_title: "Judul Label Contoh", created_at: "12/08/25 00:00", expiry_date: "19/08/25 00:00" }[field.key] || "Data Contoh";
            
            const fontSize = settings.template === 'half' ? '7pt' : '12pt';

            if (field.key === 'item_name') {
                fieldsHtml += `<div style="font-size: ${fontSize}; margin-bottom: 1.5mm; line-height: 1.1;">${valueText}</div>`;
            } else {
                fieldsHtml += `<div style="margin-bottom: 1mm; display: flex; justify-content: ${sectionJustify}; align-items: baseline;">`;
                if (!field.hideLabel) { fieldsHtml += `<strong style="margin-right: 1mm; flex-shrink: 0;">${labelText}:</strong>`; }
                fieldsHtml += `<span style="word-break: break-all; text-align: left;">${valueText}</span></div>`;
            }
        });
        
        const qrHtml = `<div class="preview-qr-placeholder" style="width: ${qrSize}; height: ${qrSize};"><span class="material-icons">qr_code_2</span></div>`;
        const separatorHtml = customTextHtml && fieldsHtml ? '<div style="width: 85%; height: 0.5px; background-color: #333; margin: 1mm auto 1.5mm auto; flex-shrink: 0;"></div>' : '';

        // *** PERUBAHAN UTAMA DI SINI ***
        let finalHtml = '';
        const contentBlock = `
            <div style="text-align: center; margin-bottom: 1mm; flex-shrink: 0;">${customTextHtml}</div>
            ${separatorHtml}
            <div style="display: flex; flex-direction: ${mainFlexDirection}; flex-grow: 1; width: 100%; overflow: hidden;">
                <div style="order: ${contentOrder}; text-align: ${contentTextAlign}; overflow: hidden; display: flex; flex-direction: column; justify-content: center; flex-grow: 1;">${fieldsHtml}</div>
                <div style="order: ${qrOrder}; align-self: ${qrAlignSelf}; text-align: center; flex-shrink: 0; margin-top: auto; padding-top: 1mm;">${qrHtml}</div>
            </div>`;

        if (settings.template === 'half') {
            finalHtml = `
                <div style="display: flex; width: 100%; height: 100%; position: relative;">
                    <div style="width: 50%; height: 100%; padding: 2mm; box-sizing: border-box; display: flex; flex-direction: column;">
                        ${contentBlock}
                    </div>
                    <div style="position: absolute; left: 50%; top: 0; bottom: 0; border-left: 1px dashed #9ca3af;"></div>
                    <div style="width: 50%; height: 100%;"></div>
                </div>`;
        } else {
            finalHtml = `
                <div style="padding: 2mm; box-sizing: border-box; display: flex; flex-direction: column; width: 100%; height: 100%; overflow: hidden;">
                    ${contentBlock}
                </div>`;
        }
        
        previewContainer.innerHTML = finalHtml;
    }

    settingsForm.addEventListener('input', updatePreview);
    settingsForm.addEventListener('change', updatePreview);
    updatePreview();
});
</script>

<?php
require_once 'footer.php';
?>