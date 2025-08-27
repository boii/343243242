<?php
/**
 * Verify Label Page (Internal) - Enriched Timeline & Internal Notes UI/UX Revamp v8
 *
 * This version adds an interactive internal notes section for authorized users,
 * completing the audit trail functionality.
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

$pageTitle = "Detail Riwayat Label";
require_once 'header.php';
// Include the QR code library
if (file_exists('libs/phpqrcode/qrlib.php')) {
    require_once 'libs/phpqrcode/qrlib.php';
}
$qrLibMissing = !class_exists('QRcode');


// --- DATA FETCHING & PROCESSING ---
$labelDetails = null;
$setInstrumentsList = [];
$pageErrorMessage = '';
$labelStatusClass = 'bg-gray-100 text-gray-800';
$labelUniqueIdFromGet = trim($_GET['uid'] ?? '');

if (empty($labelUniqueIdFromGet)) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Label tidak disediakan untuk verifikasi.'];
    header("Location: label_history.php");
    exit;
}

$conn = connectToDatabase();
if ($conn) {
    // Automatically update status to 'expired'
    $sqlUpdateExpired = "UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW() AND label_unique_id = ?";
    if ($stmtUpdate = $conn->prepare($sqlUpdateExpired)) {
        $stmtUpdate->bind_param("s", $labelUniqueIdFromGet);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    $sql = "SELECT 
                sr.*, 
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
}

render_breadcrumbs($labelDetails['label_unique_id'] ?? 'Detail');
?>

<style>
    .timeline { border-left: 3px solid #e5e7eb; }
    .timeline-item { position: relative; padding: 1rem 0 1rem 2.5rem; }
    .timeline-icon { position: absolute; left: -1.25rem; top: 1rem; display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 9999px; background-color: white; border: 3px solid #e5e7eb; }
    .timeline-item-final .timeline-icon { border-color: #3b82f6; }
    .qr-code-modal-content { background-color: white; padding: 2rem; border-radius: 0.5rem; text-align: center; }
    .qr-code-modal-content img { max-width: 250px; height: auto; margin: 0 auto; }
    .notes-display { background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 1rem; white-space: pre-wrap; font-family: monospace; font-size: 0.8rem; max-height: 200px; overflow-y: auto;}
    .item-thumbnail {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background-color: #f3f4f6;
        border: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
    }
    .item-thumbnail:hover {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    }
    .item-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .item-thumbnail .material-icons {
        font-size: 32px;
        color: #9ca3af;
    }
    /* --- PERUBAHAN: Menambahkan cursor pointer untuk thumbnail di tabel --- */
    .instrument-list-thumbnail {
        width: 40px;
        height: 40px;
        border-radius: 0.375rem; /* rounded-md */
        background-color: #f3f4f6; /* gray-100 */
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #e5e7eb; /* gray-200 */
        cursor: pointer; /* <--- Tambahan */
        transition: all 0.2s ease; /* <--- Tambahan */
    }
    .instrument-list-thumbnail:hover { /* <--- Tambahan */
        border-color: #3b82f6; /* blue-500 */
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); /* blue-500 with transparency */
    }
    .instrument-list-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .instrument-list-thumbnail .material-icons {
        color: #9ca3af; /* gray-400 */
        font-size: 20px;
    }
    /* --- AKHIR PERUBAHAN --- */

    #imageModal.active {
        opacity: 1;
        visibility: visible;
    }
    #imageModal .modal-content {
        max-width: 90vw;
        max-height: 90vh;
        width: auto;
        height: auto;
        padding: 0.5rem;
    }
    #imageModal img {
        max-width: 100%;
        max-height: calc(90vh - 4rem);
        border-radius: 0.25rem;
    }
    #imageModal .no-image-placeholder {
        padding: 4rem;
        text-align: center;
        color: #6b7280;
    }
    #imageModal .no-image-placeholder .material-icons {
        font-size: 4rem;
    }
</style>

<main class="container mx-auto px-4 sm:px-6 py-8">
    
    <div class="label-view-wrapper">
        <?php if (!empty($pageErrorMessage)): ?>
            <div class="alert alert-danger" role="alert"><span class="material-icons">error_outline</span><span><?php echo htmlspecialchars($pageErrorMessage); ?></span></div>
        <?php elseif ($labelDetails): ?>
            <div class="card p-0">
                <div class="p-6 border-b">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                        
                        <div class="flex items-start gap-4">
                            <div id="itemThumbnail" class="item-thumbnail" 
                                data-image-src="<?php 
                                    if ($labelDetails['item_type'] === 'instrument' && !empty($labelDetails['image_filename']) && file_exists('uploads/instruments/' . $labelDetails['image_filename'])) {
                                        echo 'uploads/instruments/' . htmlspecialchars($labelDetails['image_filename']);
                                    } else {
                                        echo ''; // Tanda tidak ada gambar
                                    }
                                ?>"
                                data-item-type="<?php echo htmlspecialchars($labelDetails['item_type']); ?>">
                                <?php
                                $hasImage = !empty($labelDetails['image_filename']) && file_exists('uploads/instruments/' . $labelDetails['image_filename']);
                                if ($labelDetails['item_type'] === 'instrument') {
                                    if ($hasImage) {
                                        echo '<img src="uploads/instruments/' . htmlspecialchars($labelDetails['image_filename']) . '" alt="Gambar Instrumen">';
                                    } else {
                                        echo '<span class="material-icons">build</span>';
                                    }
                                } else { // Untuk 'set'
                                    echo '<span class="material-icons">inventory_2</span>';
                                }
                                ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($labelDetails['label_title']); ?></h2>
                                <p class="font-mono text-sm text-gray-500">ID Label: <?php echo htmlspecialchars($labelDetails['label_unique_id']); ?></p>
                            </div>
                        </div>

                        <div class="label-status-banner <?php echo $labelStatusClass; ?> w-full md:w-auto mt-2 md:mt-0">
                            <span class="material-icons"><?php echo match($labelDetails['status']) { 'active' => 'check_circle', 'used' => 'task_alt', 'expired' => 'history_toggle_off', 'recalled' => 'report_problem', default => 'hourglass_top' }; ?></span>
                            <span>Status: <?php echo htmlspecialchars($labelDetails['status_display']); ?></span>
                        </div>
                    </div>
                    <div class="label-action-container !justify-start !text-left !p-0 !border-0 mt-4">
                        <?php if ($labelDetails['status'] === 'active'): ?>
                            <button type="button" id="openConfirmUsedModalBtn" class="btn bg-blue-500 text-white hover:bg-blue-600"><span class="material-icons">check_box</span>Tandai Digunakan</button>
                        <?php endif; ?>
                        <button type="button" id="showQrBtn" class="btn btn-secondary"><span class="material-icons">qr_code_2</span>Tampilkan QR</button>
                        <a href="print_router.php?label_uid=<?php echo htmlspecialchars($labelDetails['label_unique_id']); ?>" target="_blank" class="btn bg-gray-600 text-white hover:bg-gray-700"><span class="material-icons">print</span>Cetak Label</a>
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Timeline Proses</h3>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon bg-gray-100"><span class="material-icons text-gray-500">add_box</span></div>
                            <p class="font-semibold text-gray-800">Muatan Dibuat</p>
                            <p class="text-sm text-gray-600">Muatan <a href="load_detail.php?load_id=<?php echo $labelDetails['load_id']; ?>" class="font-medium text-blue-600 hover:underline"><?php echo htmlspecialchars($labelDetails['load_name'] ?? '-'); ?></a> dibuat oleh <?php echo htmlspecialchars($labelDetails['load_creator_name'] ?? 'N/A'); ?>.</p>
                            <p class="text-sm text-gray-500">Tujuan: <?php echo htmlspecialchars($labelDetails['destination_department_name'] ?? 'Stok Umum'); ?></p>
                            <time class="text-xs text-gray-400"><?php echo (new DateTime($labelDetails['load_created_at']))->format('d M Y, H:i:s'); ?></time>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-gray-100"><span class="material-icons text-gray-500">cyclone</span></div>
                            <p class="font-semibold text-gray-800">Proses Sterilisasi Selesai</p>
                            <p class="text-sm text-gray-600">Siklus: <a href="cycle_detail.php?cycle_id=<?php echo $labelDetails['cycle_id']; ?>" class="font-medium text-blue-600 hover:underline"><?php echo htmlspecialchars($labelDetails['cycle_number'] ?? '-'); ?></a> di mesin <?php echo htmlspecialchars($labelDetails['machine_name'] ?? '-'); ?>.</p>
                            <p class="text-sm text-gray-500">Operator: <?php echo htmlspecialchars($labelDetails['cycle_operator_name'] ?? 'N/A'); ?></p>
                            <time class="text-xs text-gray-400"><?php echo (new DateTime($labelDetails['cycle_date']))->format('d M Y, H:i:s'); ?></time>
                        </div>
                        <div class="timeline-item timeline-item-final">
                            <div class="timeline-icon bg-blue-100"><span class="material-icons text-blue-600">flag</span></div>
                            <p class="font-semibold text-gray-800">Status Akhir Label</p>
                            <?php if($labelDetails['status'] === 'used' && !empty($labelDetails['used_at'])): ?>
                                <p class="text-sm text-gray-600">Telah digunakan pada <?php echo (new DateTime($labelDetails['used_at']))->format('d M Y, H:i:s'); ?>.</p>
                            <?php elseif($labelDetails['status'] === 'expired'): ?>
                                <p class="text-sm text-gray-600">Kedaluwarsa pada <?php echo (new DateTime($labelDetails['expiry_date']))->format('d M Y, H:i:s'); ?>.</p>
                            <?php elseif($labelDetails['status'] === 'recalled'): ?>
                                <p class="text-sm text-red-600 font-medium">Ditarik Kembali (Recalled)</p>
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
                                            <div class="instrument-list-thumbnail"
                                                data-image-src="<?php 
                                                    if (!empty($instrument['image_filename']) && file_exists('uploads/instruments/' . $instrument['image_filename'])) {
                                                        echo 'uploads/instruments/' . htmlspecialchars($instrument['image_filename']);
                                                    } else {
                                                        echo ''; // Tanda tidak ada gambar
                                                    }
                                                ?>"
                                                data-item-type="instrument">
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

                <div class="p-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Riwayat & Catatan Internal</h3>
                    <div class="notes-display">
                        <?php echo !empty($labelDetails['notes']) ? htmlspecialchars($labelDetails['notes']) : '<span class="text-gray-500 italic">Belum ada catatan.</span>'; ?>
                    </div>
                    <?php if (in_array($userRole, ['admin', 'supervisor'])): ?>
                        <form action="php_scripts/label_add_note.php" method="POST" class="mt-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="record_id" value="<?php echo $labelDetails['record_id']; ?>">
                            <input type="hidden" name="label_unique_id" value="<?php echo $labelDetails['label_unique_id']; ?>">
                            <div>
                                <label for="internal_note" class="form-label font-medium">Tambah Catatan Baru</label>
                                <textarea name="note" id="internal_note" rows="3" class="form-input" placeholder="Masukkan catatan internal..." required></textarea>
                            </div>
                            <div class="text-right mt-2">
                                <button type="submit" class="btn btn-primary"><span class="material-icons mr-2">add_comment</span>Simpan Catatan</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <form id="markAsUsedForm" action="php_scripts/update_label_status_action.php" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="record_id_to_update" value="<?php echo $labelDetails['record_id'] ?? ''; ?>">
        <input type="hidden" name="label_unique_id_for_redirect" value="<?php echo $labelDetails['label_unique_id'] ?? ''; ?>">
        <input type="hidden" name="action_type" value="mark_as_used">
    </form>

    <div id="confirmUsedModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="text-lg font-bold mb-2">Konfirmasi Aksi</h3>
            <p class="mb-4">Apakah Anda yakin ingin menandai label ini telah digunakan?</p>
            <div class="flex justify-center gap-4">
                <button type="button" id="cancelConfirmUsedBtn" class="btn btn-secondary">Batal</button>
                <button type="button" id="submitConfirmUsedBtn" class="btn btn-primary bg-blue-500 hover:bg-blue-600">Ya, Tandai Digunakan</button>
            </div>
        </div>
    </div>
    
    <div id="qrCodeModal" class="modal-overlay"><div class="qr-code-modal-content"><h3 class="text-lg font-bold mb-4">QR Code Label</h3><div id="qrCodeImageContainer"><?php if (!$qrLibMissing && !empty($labelDetails['label_unique_id'])) { $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://"; $host = $_SERVER['HTTP_HOST']; $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); if ($basePath === '/' || $basePath === '\\') $basePath = ''; $qrData = $protocol . $host . $basePath . "/handle_qr_scan.php?uid=" . urlencode($labelDetails['label_unique_id']); ob_start(); QRcode::png($qrData, null, QR_ECLEVEL_L, 10, 2); $imageData = ob_get_contents(); ob_end_clean(); echo '<img src="data:image/png;base64,' . base64_encode($imageData) . '" alt="QR Code">'; } else { echo '<p class="text-red-500">Gagal membuat QR Code.</p>'; } ?></div><p class="text-sm text-gray-600 mt-2 font-mono"><?php echo htmlspecialchars($labelDetails['label_unique_id'] ?? ''); ?></p><button id="closeQrModalBtn" class="btn btn-secondary mt-6">Tutup</button></div></div>

    <div id="imageModal" class="modal-overlay">
        <div class="modal-content">
            <img id="modalImage" src="" alt="Gambar Instrumen Diperbesar" class="hidden">
            <div id="noImagePlaceholder" class="no-image-placeholder hidden">
                <span class="material-icons"></span>
                <p class="mt-2 font-semibold"></p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showQrBtn = document.getElementById('showQrBtn');
    const qrModal = document.getElementById('qrCodeModal');
    const closeQrModalBtn = document.getElementById('closeQrModalBtn');

    const openConfirmUsedModalBtn = document.getElementById('openConfirmUsedModalBtn');
    const confirmUsedModal = document.getElementById('confirmUsedModal');
    const cancelConfirmUsedBtn = document.getElementById('cancelConfirmUsedBtn');
    const submitConfirmUsedBtn = document.getElementById('submitConfirmUsedBtn');
    const markAsUsedForm = document.getElementById('markAsUsedForm');

    // --- PERUBAHAN: Dapatkan referensi ke elemen modal gambar & isinya ---
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const noImagePlaceholder = document.getElementById('noImagePlaceholder');
    const noImageIcon = noImagePlaceholder.querySelector('.material-icons');
    const noImageText = noImagePlaceholder.querySelector('p');

    // Fungsi untuk menampilkan modal gambar dengan konten yang sesuai
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

    // Event listener untuk thumbnail utama (atas)
    const itemThumbnail = document.getElementById('itemThumbnail');
    if (itemThumbnail) {
        itemThumbnail.addEventListener('click', () => {
            const imageSrc = itemThumbnail.dataset.imageSrc;
            const itemType = itemThumbnail.dataset.itemType;
            showImageModal(imageSrc, itemType);
        });
    }

    // --- PERUBAHAN: Event listener untuk semua thumbnail di tabel rincian set ---
    const instrumentThumbnails = document.querySelectorAll('.instrument-list-thumbnail');
    instrumentThumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', () => {
            const imageSrc = thumbnail.dataset.imageSrc;
            const itemType = thumbnail.dataset.itemType; // Ini akan selalu 'instrument'
            showImageModal(imageSrc, itemType);
        });
    });
    // --- AKHIR PERUBAHAN ---

    // Menutup modal gambar saat mengklik area gelap di sekitarnya
    if (imageModal) {
        imageModal.addEventListener('click', (e) => {
            if (e.target === imageModal) {
                imageModal.classList.remove('active');
            }
        });
    }

    if (showQrBtn && qrModal) { showQrBtn.addEventListener('click', () => qrModal.classList.add('active')); }
    if (closeQrModalBtn && qrModal) { closeQrModalBtn.addEventListener('click', () => qrModal.classList.remove('active')); }
    if(qrModal) { qrModal.addEventListener('click', (e) => { if (e.target === qrModal) qrModal.classList.remove('active'); }); }

    if (openConfirmUsedModalBtn && confirmUsedModal) {
        openConfirmUsedModalBtn.addEventListener('click', () => {
            confirmUsedModal.classList.add('active');
        });
    }
    if (cancelConfirmUsedBtn && confirmUsedModal) {
        cancelConfirmUsedBtn.addEventListener('click', () => {
            confirmUsedModal.classList.remove('active');
        });
    }
    if (submitConfirmUsedBtn && markAsUsedForm) {
        submitConfirmUsedBtn.addEventListener('click', () => {
            markAsUsedForm.submit();
        });
    }
    if(confirmUsedModal) {
        confirmUsedModal.addEventListener('click', (e) => {
            if (e.target === confirmUsedModal) {
                confirmUsedModal.classList.remove('active');
            }
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>