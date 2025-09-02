<?php
/**
 * Public Label View Page - Enriched Timeline UI/UX Revamp v11 (FINAL with Issue Image Upload)
 *
 * This version adds an elegant image upload to the "Report Issue" modal,
 * and displays the proof image in the timeline for recalled items.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Frontend
 * @package  Sterilabel
 * @author   UI/UX Specialist
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once 'config.php';
// Include the QR code library
if (file_exists('libs/phpqrcode/qrlib.php')) {
    require_once 'libs/phpqrcode/qrlib.php';
}
$qrLibMissing = !class_exists('QRcode');

// --- DATA FETCHING & CONFIGURATION ---
$labelDetails = null;
$setInstrumentsList = [];
$pageErrorMessage = '';
$labelStatusClass = 'bg-gray-100 text-gray-800';
$labelUniqueIdFromGet = trim($_GET['uid'] ?? '');

$showStatusBlock = (bool)($app_settings['show_status_block_on_detail_page'] ?? true);

if (empty($labelUniqueIdFromGet)) {
    $pageErrorMessage = "ID Label tidak disediakan.";
} else {
    $conn = connectToDatabase();
    if ($conn) {
        $sqlUpdateExpired = "UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW() AND label_unique_id = ?";
        if ($stmtUpdate = $conn->prepare($sqlUpdateExpired)) {
            $stmtUpdate->bind_param("s", $labelUniqueIdFromGet);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        $sql = "SELECT
                    sr.*, sr.usage_proof_filename, sr.issue_proof_filename,
                    creator.full_name as creator_full_name,
                    validator.full_name as validator_full_name,
                    sc.machine_name, sc.cycle_number, sc.cycle_date, sc.status as cycle_status,
                    cycle_operator.full_name as cycle_operator_name,
                    sl.load_name, sl.created_at as load_created_at, load_creator.full_name as load_creator_name,
                    dest_dept.department_name as destination_department_name,
                    i.image_filename
                FROM sterilization_records sr
                LEFT JOIN users creator ON sr.created_by_user_id = creator.user_id
                LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
                LEFT JOIN users load_creator ON sl.created_by_user_id = load_creator.user_id
                LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
                LEFT JOIN users cycle_operator ON sc.operator_user_id = cycle_operator.user_id
                LEFT JOIN users validator ON sr.validated_by_user_id = validator.user_id
                LEFT JOIN departments dest_dept ON sl.destination_department_id = dest_dept.department_id
                LEFT JOIN instruments i ON sr.item_type = 'instrument' AND sr.item_id = i.instrument_id
                WHERE sr.label_unique_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $labelUniqueIdFromGet);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $labelDetails = $result->fetch_assoc();
                $statusInfo = getUniversalStatusBadge($labelDetails['status']);
                $labelDetails['status_display'] = $statusInfo['text'];
                $labelStatusClass = $statusInfo['class'];

                if ($labelDetails['item_type'] === 'set') {
                    $snapshotData = json_decode($labelDetails['label_items_snapshot'] ?? '[]', true);
                    if (is_array($snapshotData) && !empty($snapshotData)) {
                        $instrumentIds = array_column($snapshotData, 'instrument_id');
                        if (!empty($instrumentIds)) {
                            $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
                            $sqlSnapshotDetails = "SELECT instrument_id, instrument_name, instrument_code, image_filename FROM instruments WHERE instrument_id IN ($placeholders)";
                            if($stmtSnapshot = $conn->prepare($sqlSnapshotDetails)){
                                $stmtSnapshot->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
                                $stmtSnapshot->execute();
                                $instrumentDetailsMap = [];
                                $resultSnapshot = $stmtSnapshot->get_result();
                                while($row = $resultSnapshot->fetch_assoc()){ $instrumentDetailsMap[$row['instrument_id']] = $row; }
                                $stmtSnapshot->close();
                                foreach($snapshotData as $item){
                                    $setInstrumentsList[] = [
                                        'instrument_name' => $instrumentDetailsMap[$item['instrument_id']]['instrument_name'] ?? 'Instrumen Dihapus',
                                        'instrument_code' => $instrumentDetailsMap[$item['instrument_id']]['instrument_code'] ?? '-',
                                        'quantity' => $item['quantity'],
                                        'image_filename' => $instrumentDetailsMap[$item['instrument_id']]['image_filename'] ?? null
                                    ];
                                }
                            }
                        }
                    }
                }
            } else {
                $pageErrorMessage = "Label dengan ID '" . htmlspecialchars($labelUniqueIdFromGet) . "' tidak ditemukan.";
            }
            $stmt->close();
        } else {
            $pageErrorMessage = "Gagal mempersiapkan query: " . $conn->error;
        }
        $conn->close();
    } else {
        $pageErrorMessage = "Koneksi ke database gagal.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Label - <?php echo htmlspecialchars($labelDetails['label_unique_id'] ?? 'Tidak Ditemukan'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body { padding: 20px; }
        .form-textarea { min-height: 80px; }
        .timeline { border-left: 3px solid #e5e7eb; }
        .timeline-item { position: relative; padding: 1rem 0 1rem 2.5rem; }
        .timeline-icon { position: absolute; left: -1.25rem; top: 1rem; display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 9999px; background-color: white; border: 3px solid #e5e7eb; }
        .timeline-item-final .timeline-icon { border-color: #3b82f6; }
        .item-thumbnail {
            width: 64px; height: 64px; border-radius: 50%; background-color: #f3f4f6;
            border: 2px solid #e5e7eb; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; cursor: pointer; transition: all 0.2s ease; overflow: hidden;
        }
        .item-thumbnail:hover {
            border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }
        .item-thumbnail img { width: 100%; height: 100%; object-fit: cover; }
        .item-thumbnail .material-icons { font-size: 32px; color: #9ca3af; }
        .instrument-list-thumbnail { cursor: pointer; transition: all 0.2s ease; }
        .instrument-list-thumbnail:hover { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        #imageModal.active { opacity: 1; visibility: visible; }
        #imageModal .modal-content { max-width: 90vw; max-height: 90vh; width: auto; height: auto; padding: 0.5rem; }
        #imageModal img { max-width: 100%; max-height: calc(90vh - 4rem); border-radius: 0.25rem; }
        #imageModal .no-image-placeholder { padding: 4rem; text-align: center; color: #6b7280; }
        #imageModal .no-image-placeholder .material-icons { font-size: 4rem; }
        .file-upload-wrapper {
            position: relative; border: 2px dashed #d1d5db; border-radius: 0.5rem;
            padding: 1.5rem; text-align: center; cursor: pointer; transition: border-color 0.2s, background-color 0.2s;
        }
        .file-upload-wrapper:hover { border-color: #3b82f6; background-color: #f9fafb; }
        .file-upload-wrapper input[type="file"] { position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; }
        .file-upload-wrapper .upload-icon { font-size: 2.5rem; color: #9ca3af; }
        .file-upload-wrapper .upload-text { color: #6b7280; font-weight: 500; }
        .file-upload-wrapper .upload-hint { font-size: 0.75rem; color: #9ca3af; }
        .image-preview-container { margin-top: 1rem; display: none; }
        .image-preview-container img { max-width: 100%; max-height: 150px; border-radius: 0.5rem; border: 1px solid #e5e7eb; object-fit: contain; }
        .proof-thumbnail-container { margin-top: 0.75rem; }
        .proof-thumbnail {
            width: 80px; height: 80px; border-radius: 0.5rem; border: 2px solid #e5e7eb;
            object-fit: cover; cursor: pointer; transition: all 0.2s ease;
        }
        .proof-thumbnail:hover { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2); }
    </style>
</head>
<body class="bg-gray-100">
    <div class="label-view-wrapper">
        <div id="ajax-message-container"></div>
        <?php if ($labelDetails): ?>
            <div class="card p-0">
                <div class="p-6 border-b">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                        <div class="flex items-start gap-4">
                            <div id="itemThumbnail" class="item-thumbnail"
                                data-image-src="<?php if ($labelDetails['item_type'] === 'instrument' && !empty($labelDetails['image_filename']) && file_exists('uploads/instruments/' . $labelDetails['image_filename'])) { echo 'uploads/instruments/' . htmlspecialchars($labelDetails['image_filename']); } else { echo ''; } ?>"
                                data-item-type="<?php echo htmlspecialchars($labelDetails['item_type']); ?>">
                                <?php
                                $hasImage = !empty($labelDetails['image_filename']) && file_exists('uploads/instruments/' . $labelDetails['image_filename']);
                                if ($labelDetails['item_type'] === 'instrument') {
                                    echo $hasImage ? '<img src="uploads/instruments/' . htmlspecialchars($labelDetails['image_filename']) . '" alt="Gambar Instrumen">' : '<span class="material-icons">build</span>';
                                } else {
                                    echo '<span class="material-icons">inventory_2</span>';
                                }
                                ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($labelDetails['label_title']); ?></h2>
                                <p class="font-mono text-sm text-gray-500">ID Label: <?php echo htmlspecialchars($labelDetails['label_unique_id']); ?></p>
                            </div>
                        </div>
                        <?php if ($showStatusBlock): ?>
                        <div class="label-status-banner <?php echo $labelStatusClass; ?> w-full md:w-auto mt-2 md:mt-0">
                            <span class="material-icons"><?php echo match($labelDetails['status']) { 'active' => 'check_circle', 'used' => 'task_alt', 'expired' => 'history_toggle_off', 'recalled' => 'report_problem', default => 'hourglass_top' }; ?></span>
                            <span>Status: <?php echo htmlspecialchars($labelDetails['status_display']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (strtolower($labelDetails['status']) === 'active'): ?>
                    <div class="label-action-container !justify-start !text-left !p-0 !border-0 mt-4">
                        <button id="markUsedBtn" class="btn bg-blue-500 text-white hover:bg-blue-600"><span class="material-icons">check_box</span>Tandai Digunakan</button>
                        <button id="reportIssueBtn" class="btn bg-yellow-500 text-white hover:bg-yellow-600"><span class="material-icons">report_problem</span>Laporkan Masalah</button>
                    </div>
                    <div class="mt-4 p-3 bg-gray-100 rounded-lg flex items-start text-xs text-gray-600">
                        <span class="material-icons text-base mr-2 text-gray-500">policy</span>
                        <span>Mohon perhatian: Semua aksi yang dilakukan pada halaman ini (termasuk alamat IP dan detail perangkat Anda) akan dicatat oleh sistem untuk tujuan audit dan akuntabilitas.</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Timeline Proses</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon bg-gray-100"><span class="material-icons text-gray-500">add_box</span></div>
                            <p class="font-semibold text-gray-800">Muatan Dibuat</p>
                            <p class="text-sm text-gray-600">Muatan <span class="font-medium"><?php echo htmlspecialchars($labelDetails['load_name'] ?? '-'); ?></span> dibuat oleh <?php echo htmlspecialchars($labelDetails['load_creator_name'] ?? 'N/A'); ?>.</p>
                            <p class="text-sm text-gray-500">Tujuan: <?php echo htmlspecialchars($labelDetails['destination_department_name'] ?? 'Stok Umum'); ?></p>
                            <time class="text-xs text-gray-400"><?php echo (new DateTime($labelDetails['load_created_at']))->format('d M Y, H:i:s'); ?></time>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-gray-100"><span class="material-icons text-gray-500">cyclone</span></div>
                            <p class="font-semibold text-gray-800">Proses Sterilisasi Selesai</p>
                            <p class="text-sm text-gray-600">Siklus: <span class="font-medium"><?php echo htmlspecialchars($labelDetails['cycle_number'] ?? '-'); ?></span> di mesin <?php echo htmlspecialchars($labelDetails['machine_name'] ?? '-'); ?>.</p>
                            <p class="text-sm text-gray-500">Operator: <?php echo htmlspecialchars($labelDetails['cycle_operator_name'] ?? 'N/A'); ?></p>
                            <time class="text-xs text-gray-400"><?php echo (new DateTime($labelDetails['cycle_date']))->format('d M Y, H:i:s'); ?></time>
                        </div>
                         <div class="timeline-item timeline-item-final">
                            <div class="timeline-icon bg-blue-100"><span class="material-icons text-blue-600">flag</span></div>
                            <p class="font-semibold text-gray-800">Status Akhir Label</p>
                            <?php if($labelDetails['status'] === 'used' && !empty($labelDetails['used_at'])): ?>
                                <p class="text-sm text-gray-600">Telah digunakan pada <?php echo (new DateTime($labelDetails['used_at']))->format('d M Y, H:i:s'); ?>.</p>
                                <?php if (!empty($labelDetails['usage_proof_filename']) && file_exists('uploads/usage_proof/' . $labelDetails['usage_proof_filename'])): ?>
                                    <div class="proof-thumbnail-container">
                                        <img src="uploads/usage_proof/<?php echo htmlspecialchars($labelDetails['usage_proof_filename']); ?>" alt="Bukti Penggunaan" class="proof-thumbnail" onclick="showImageModal('uploads/usage_proof/<?php echo htmlspecialchars($labelDetails['usage_proof_filename']); ?>', 'instrument')">
                                    </div>
                                <?php endif; ?>
                            <?php elseif($labelDetails['status'] === 'expired'): ?>
                                <p class="text-sm text-gray-600">Kedaluwarsa pada <?php echo (new DateTime($labelDetails['expiry_date']))->format('d M Y, H:i:s'); ?>.</p>
                            <?php elseif($labelDetails['status'] === 'recalled'): ?>
                                <p class="text-sm text-red-600 font-medium">Ditarik Kembali (Recalled)</p>
                                <?php
                                $publicNotes = '';
                                if (!empty($labelDetails['notes'])) {
                                    $notesArray = explode("\n", $labelDetails['notes']);
                                    foreach ($notesArray as $note) {
                                        if (strpos($note, 'CATATAN INTERNAL') === false && strpos($note, '-----------------') === false) {
                                            $publicNotes .= $note . "\n";
                                        }
                                    }
                                    $publicNotes = trim($publicNotes);
                                }
                                if (!empty($publicNotes)):
                                ?>
                                <p class="text-xs text-gray-500 bg-red-50 p-2 rounded-md mt-1">Catatan: <?php echo nl2br(htmlspecialchars($publicNotes)); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($labelDetails['issue_proof_filename']) && file_exists('uploads/issue_proof/' . $labelDetails['issue_proof_filename'])): ?>
                                    <div class="proof-thumbnail-container">
                                        <img src="uploads/issue_proof/<?php echo htmlspecialchars($labelDetails['issue_proof_filename']); ?>" alt="Bukti Masalah" class="proof-thumbnail" onclick="showImageModal('uploads/issue_proof/<?php echo htmlspecialchars($labelDetails['issue_proof_filename']); ?>', 'instrument')">
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-sm text-gray-600">Label masih aktif dan siap digunakan hingga <?php echo (new DateTime($labelDetails['expiry_date']))->format('d M Y, H:i:s'); ?>.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($setInstrumentsList)): ?>
                    <div class="p-6 border-t">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Rincian Instrumen dalam Set</h3>
                        <div class="overflow-x-auto">
                            <table class="instrument-list-table">
                                <thead><tr><th class="w-16">Gambar</th><th>Nama</th><th>Kode</th><th class="text-center">Kuantitas</th></tr></thead>
                                <tbody>
                                    <?php foreach ($setInstrumentsList as $instrument): ?>
                                    <tr>
                                        <td>
                                            <div class="instrument-list-thumbnail" data-image-src="<?php if (!empty($instrument['image_filename']) && file_exists('uploads/instruments/' . $instrument['image_filename'])) { echo 'uploads/instruments/' . htmlspecialchars($instrument['image_filename']); } else { echo ''; } ?>" data-item-type="instrument">
                                                <?php if (!empty($instrument['image_filename']) && file_exists('uploads/instruments/' . $instrument['image_filename'])): ?>
                                                    <img src="uploads/instruments/<?php echo htmlspecialchars($instrument['image_filename']); ?>" alt="Gambar Instrumen">
                                                <?php else: ?>
                                                    <span class="material-icons">build</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($instrument['instrument_name']); ?></td>
                                        <td><?php echo htmlspecialchars($instrument['instrument_code'] ?? '-'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars((string)$instrument['quantity']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card p-6">
                 <div class="alert alert-danger" role="alert"><span class="material-icons">error</span><span><?php echo htmlspecialchars($pageErrorMessage ?: 'Gagal memuat detail label atau ID tidak valid.'); ?></span></div>
            </div>
        <?php endif; ?>
    </div>

    <div id="confirmUsedModal" class="modal-overlay">
        <div class="modal-content max-w-lg">
            <h3 class="text-lg font-bold mb-2">Konfirmasi Penggunaan</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menandai item ini telah digunakan? Unggah foto sebagai bukti jika diperlukan.</p>
            <form id="confirmUsedForm">
                <div class="mb-4 text-left">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bukti Foto (Opsional)</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="usage_proof_image" accept="image/jpeg, image/png, image/webp">
                        <span class="material-icons upload-icon">add_a_photo</span>
                        <p class="upload-text">Klik atau seret gambar ke sini</p>
                        <p class="upload-hint">JPG, PNG, atau WEBP (Maks 2MB)</p>
                    </div>
                    <div class="image-preview-container">
                        <img src="#" alt="Pratinjau Gambar"/>
                    </div>
                </div>
                <div class="mb-4 text-left">
                    <label for="usedReason" class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional, misal: digunakan oleh siapa)</label>
                    <textarea name="note" class="form-input form-textarea w-full" placeholder="Contoh: Digunakan oleh Dr. Budi"></textarea>
                </div>
                <div class="flex justify-center gap-4">
                    <button type="button" class="btn-cancel-modal btn bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</button>
                    <button type="submit" class="btn bg-blue-500 text-white hover:bg-blue-600">
                        <span class="material-icons mr-2">check_circle</span>Ya, Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="issueModal" class="modal-overlay">
        <div class="modal-content max-w-lg">
            <h3 class="text-lg font-bold mb-2">Laporkan Masalah</h3>
            <p class="text-sm text-gray-600 mb-4">Item akan ditandai sebagai "Ditarik Kembali". Jelaskan masalahnya dan unggah foto jika ada.</p>
            <form id="issueForm">
                <div class="mb-4 text-left">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bukti Foto (Opsional)</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="issue_proof_image" accept="image/jpeg, image/png, image/webp">
                        <span class="material-icons upload-icon">add_a_photo</span>
                        <p class="upload-text">Klik atau seret gambar ke sini</p>
                        <p class="upload-hint">JPG, PNG, atau WEBP (Maks 2MB)</p>
                    </div>
                    <div class="image-preview-container">
                        <img src="#" alt="Pratinjau Gambar"/>
                    </div>
                </div>
                <div class="mb-4 text-left">
                    <label for="issueReason" class="block text-sm font-medium text-gray-700 mb-1">Alasan <span class="text-red-500">*</span></label>
                    <textarea name="reason" class="form-input form-textarea w-full" placeholder="Contoh: Kemasan sobek atau indikator gagal" required></textarea>
                </div>
                <div class="flex justify-center gap-4">
                    <button type="button" class="btn-cancel-modal btn bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</button>
                    <button type="submit" class="btn bg-yellow-500 text-white hover:bg-yellow-600">
                        <span class="material-icons mr-2">send</span>Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="imageModal" class="modal-overlay">
        <div class="modal-content">
            <img id="modalImage" src="" alt="Gambar Instrumen Diperbesar" class="hidden">
            <div id="noImagePlaceholder" class="no-image-placeholder hidden">
                <span class="material-icons"></span>
                <p class="mt-2 font-semibold"></p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const markUsedBtn = document.getElementById('markUsedBtn');
        const reportIssueBtn = document.getElementById('reportIssueBtn');
        const confirmUsedModal = document.getElementById('confirmUsedModal');
        const issueModal = document.getElementById('issueModal');
        const confirmUsedForm = document.getElementById('confirmUsedForm');
        const issueForm = document.getElementById('issueForm');
        const messageContainer = document.getElementById('ajax-message-container');
        const statusBlock = document.querySelector('.label-status-banner');
        const actionContainer = document.querySelector('.label-action-container');
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const noImagePlaceholder = document.getElementById('noImagePlaceholder');
        const noImageIcon = noImagePlaceholder.querySelector('.material-icons');
        const noImageText = noImagePlaceholder.querySelector('p');

        function showImageModal(imageSrc, itemType) {
            if (imageSrc) {
                modalImage.src = imageSrc;
                modalImage.classList.remove('hidden');
                noImagePlaceholder.classList.add('hidden');
            } else {
                modalImage.classList.add('hidden');
                noImagePlaceholder.classList.remove('hidden');
                noImageIcon.textContent = itemType === 'instrument' ? 'build' : 'inventory_2';
                noImageText.textContent = 'Tidak ada gambar untuk item ini.';
            }
            imageModal.classList.add('active');
        }

        [itemThumbnail, ...document.querySelectorAll('.instrument-list-thumbnail')].forEach(thumb => {
            if(thumb) thumb.addEventListener('click', () => showImageModal(thumb.dataset.imageSrc, thumb.dataset.itemType));
        });

        if (imageModal) { imageModal.addEventListener('click', e => { if (e.target === imageModal) imageModal.classList.remove('active'); }); }

        const allModals = document.querySelectorAll('.modal-overlay');
        allModals.forEach(modal => { modal.addEventListener('click', function(e) { if (e.target === modal || e.target.closest('.btn-cancel-modal')) { modal.classList.remove('active'); } }); });

        if (markUsedBtn) { markUsedBtn.addEventListener('click', () => confirmUsedModal.classList.add('active')); }
        if (reportIssueBtn) { reportIssueBtn.addEventListener('click', () => issueModal.classList.add('active')); }

        function compressImage(file, options) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.src = URL.createObjectURL(file);
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let { width, height } = img;
                    if (width > height) {
                        if (width > options.maxWidth) { height *= options.maxWidth / width; width = options.maxWidth; }
                    } else {
                        if (height > options.maxHeight) { width *= options.maxHeight / height; height = options.maxHeight; }
                    }
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    canvas.toBlob( (blob) => {
                        if (!blob) { reject(new Error('Canvas to Blob conversion failed.')); return; }
                        resolve(new File([blob], file.name, { type: options.mimeType, lastModified: Date.now() }));
                    }, options.mimeType, options.quality);
                };
                img.onerror = (error) => reject(error);
            });
        }

        function setupForm(formElement, endpoint, successCallback) {
            if (!formElement) return;

            const imageInput = formElement.querySelector('input[type="file"]');
            const previewContainer = formElement.querySelector('.image-preview-container');
            const previewImage = previewContainer ? previewContainer.querySelector('img') : null;
            const submitBtn = formElement.querySelector('button[type="submit"]');

            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file && previewContainer && previewImage) {
                        const reader = new FileReader();
                        reader.onload = e => { previewImage.src = e.target.result; previewContainer.style.display = 'block'; };
                        reader.readAsDataURL(file);
                    } else if (previewContainer) {
                        previewContainer.style.display = 'none';
                    }
                });
            }

            formElement.addEventListener('submit', async function(e) {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="material-icons animate-spin mr-2">sync</span>Memproses...`;
                const formData = new FormData(this);
                formData.append('label_uid', '<?php echo htmlspecialchars($labelUniqueIdFromGet ?? ""); ?>');
                if (imageInput && imageInput.files.length > 0) {
                    const originalFile = imageInput.files[0];
                    try {
                        const compressedFile = await compressImage(originalFile, { maxWidth: 1024, maxHeight: 1024, quality: 0.8, mimeType: 'image/jpeg' });
                        formData.set(imageInput.name, compressedFile);
                    } catch (error) {
                        console.error("Image compression failed:", error);
                    }
                }
                fetch(endpoint, { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        successCallback(data, formElement.closest('.modal-overlay'));
                        formElement.reset();
                        if(previewContainer) previewContainer.style.display = 'none';
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = formElement === confirmUsedForm ? '<span class="material-icons mr-2">check_circle</span>Ya, Konfirmasi' : '<span class="material-icons mr-2">send</span>Kirim Laporan';
                    });
            });
        }

        setupForm(confirmUsedForm, 'php_scripts/public_mark_used.php', (data, modal) => {
            handleResponse(data, modal, 'Telah Digunakan', 'bg-blue-100 text-blue-800', 'task_alt');
        });

        setupForm(issueForm, 'php_scripts/mark_label_compromised.php', (data, modal) => {
            handleResponse(data, modal, 'Ditarik Kembali', 'bg-purple-100 text-purple-800', 'report_problem');
        });

        function handleResponse(data, modal, newStatusText, newStatusClass, newIcon) {
            if (data.success) {
                modal.classList.remove('active');
                displayMessage(data.message, 'success');
                if (statusBlock) {
                    const iconEl = statusBlock.querySelector('.material-icons');
                    const textEl = statusBlock.querySelector('span:last-child');
                    statusBlock.className = `label-status-banner ${newStatusClass}`;
                    if(textEl) textEl.textContent = `Status: ${newStatusText}`;
                    if(iconEl) iconEl.textContent = newIcon;
                }
                if(actionContainer) { actionContainer.innerHTML = `<p class="text-sm text-gray-600">Aksi untuk label ini telah selesai.</p>`; }
                setTimeout(() => location.reload(), 1500);
            } else {
                displayMessage(data.message || 'Terjadi kesalahan.', 'danger');
                modal.classList.remove('active');
            }
        }

        function displayMessage(message, type) { const alertClass = type === 'success' ? 'alert-success' : 'alert-danger'; const icon = type === 'success' ? 'check_circle' : 'error'; messageContainer.innerHTML = `<div class="alert ${alertClass} mb-4" role="alert"><span class="material-icons">${icon}</span><span>${message}</span></div>`; window.scrollTo(0, 0); }
    });
    </script>
</body>
</html>