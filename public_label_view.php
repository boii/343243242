<?php
/**
 * Public Label View Page - Enriched Timeline UI/UX Revamp v10 (FINAL)
 *
 * This version adds an elegant accountability notice below the public action
 * buttons to inform users that their actions are being logged. It also
 * includes a note field in the "Mark as Used" action.
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
        // Automatically update status to 'expired'
        $sqlUpdateExpired = "UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW() AND label_unique_id = ?";
        if ($stmtUpdate = $conn->prepare($sqlUpdateExpired)) {
            $stmtUpdate->bind_param("s", $labelUniqueIdFromGet);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        // Enhanced SQL Query to fetch all lifecycle details
        $sql = "SELECT 
                    sr.*, 
                    creator.full_name as creator_full_name, 
                    validator.full_name as validator_full_name,
                    sc.machine_name, sc.cycle_number, sc.cycle_date, sc.status as cycle_status,
                    cycle_operator.full_name as cycle_operator_name,
                    sl.load_name, sl.created_at as load_created_at, load_creator.full_name as load_creator_name,
                    dest_dept.department_name as destination_department_name
                FROM sterilization_records sr
                LEFT JOIN users creator ON sr.created_by_user_id = creator.user_id
                LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
                LEFT JOIN users load_creator ON sl.created_by_user_id = load_creator.user_id
                LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
                LEFT JOIN users cycle_operator ON sc.operator_user_id = cycle_operator.user_id
                LEFT JOIN users validator ON sr.validated_by_user_id = validator.user_id
                LEFT JOIN departments dest_dept ON sl.destination_department_id = dest_dept.department_id
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
                            $sqlSnapshotDetails = "SELECT instrument_id, instrument_name, instrument_code FROM instruments WHERE instrument_id IN ($placeholders)";
                            if($stmtSnapshot = $conn->prepare($sqlSnapshotDetails)){
                                $stmtSnapshot->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
                                $stmtSnapshot->execute();
                                $instrumentDetailsMap = [];
                                $resultSnapshot = $stmtSnapshot->get_result();
                                while($row = $resultSnapshot->fetch_assoc()){ $instrumentDetailsMap[$row['instrument_id']] = $row; }
                                $stmtSnapshot->close();
                                
                                foreach($snapshotData as $item){ 
                                    $setInstrumentsList[] = [ 'instrument_name' => $instrumentDetailsMap[$item['instrument_id']]['instrument_name'] ?? 'Instrumen Dihapus', 'instrument_code' => $instrumentDetailsMap[$item['instrument_id']]['instrument_code'] ?? '-', 'quantity' => $item['quantity'] ];
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="label-view-wrapper">
        <div id="ajax-message-container"></div>

        <?php if ($labelDetails): ?>
            <div class="card p-0">
                <div class="p-6 border-b">
                     <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($labelDetails['label_title']); ?></h2>
                            <p class="font-mono text-sm text-gray-500">ID Label: <?php echo htmlspecialchars($labelDetails['label_unique_id']); ?></p>
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
                                <thead><tr><th>Nama</th><th>Kode</th><th class="text-center">Kuantitas</th></tr></thead>
                                <tbody><?php foreach ($setInstrumentsList as $instrument): ?><tr><td><?php echo htmlspecialchars($instrument['instrument_name']); ?></td><td><?php echo htmlspecialchars($instrument['instrument_code'] ?? '-'); ?></td><td class="text-center"><?php echo htmlspecialchars((string)$instrument['quantity']); ?></td></tr><?php endforeach; ?></tbody>
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
        <div class="modal-content">
            <h3 class="text-lg font-bold mb-2">Konfirmasi Penggunaan</h3>
            <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menandai item ini telah digunakan?</p>
            <form id="confirmUsedForm">
                <div class="mb-4 text-left">
                    <label for="usedReason" class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional, misal: digunakan oleh siapa)</label>
                    <textarea id="usedReason" class="form-input form-textarea w-full" placeholder="Contoh: Digunakan oleh Dr. Budi"></textarea>
                </div>
                <div class="flex justify-center gap-4">
                    <button type="button" class="btn-cancel-modal btn bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</button>
                    <button type="submit" class="btn bg-blue-500 text-white hover:bg-blue-600">Ya, Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
    <div id="issueModal" class="modal-overlay"><div class="modal-content"><h3 class="text-lg font-bold mb-2">Laporkan Masalah</h3><p class="text-sm text-gray-600 mb-4">Item akan ditandai sebagai "Ditarik Kembali". Jelaskan masalahnya.</p><form id="issueForm"><div class="mb-4"><label for="issueReason" class="block text-left text-sm font-medium text-gray-700 mb-1">Alasan (Contoh: Kemasan sobek)</label><textarea id="issueReason" class="form-input form-textarea w-full" required></textarea></div><div class="flex justify-center gap-4"><button type="button" class="btn-cancel-modal btn bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</button><button type="submit" class="btn bg-yellow-500 text-white hover:bg-yellow-600">Kirim Laporan</button></div></form></div></div>

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

        const allModals = document.querySelectorAll('.modal-overlay');
        allModals.forEach(modal => { modal.addEventListener('click', function(e) { if (e.target === modal || e.target.closest('.btn-cancel-modal')) { modal.classList.remove('active'); } }); });

        if (markUsedBtn) { markUsedBtn.addEventListener('click', () => { confirmUsedModal.classList.add('active'); }); }
        if (reportIssueBtn) { reportIssueBtn.addEventListener('click', () => { issueModal.classList.add('active'); document.getElementById('issueReason').focus(); }); }

        if (confirmUsedForm) {
            confirmUsedForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const reasonInput = document.getElementById('usedReason');
                const note = reasonInput.value.trim();
                
                fetch('php_scripts/public_mark_used.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        label_uid: '<?php echo htmlspecialchars($labelUniqueIdFromGet ?? ""); ?>',
                        note: note
                    })
                })
                .then(response => response.json())
                .then(data => handleResponse(data, confirmUsedModal, 'Telah Digunakan', 'bg-blue-100 text-blue-800', 'task_alt'));
            });
        }
        if (issueForm) { issueForm.addEventListener('submit', function(e) { e.preventDefault(); const reasonInput = document.getElementById('issueReason'); fetch('php_scripts/mark_label_compromised.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ label_uid: '<?php echo htmlspecialchars($labelUniqueIdFromGet ?? ""); ?>', reason: reasonInput.value }) }).then(response => response.json()).then(data => handleResponse(data, issueModal, 'Ditarik Kembali', 'bg-purple-100 text-purple-800', 'report_problem')); }); }

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