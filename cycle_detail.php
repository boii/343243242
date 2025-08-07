<?php
/**
 * Cycle Detail Page (Read-Only View - Simplified)
 *
 * This version aligns the UI of the details card with other pages,
 * using the improved detail grid format with icons.
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

$pageTitle = "Detail Siklus";
require_once 'header.php';

$cycleId = filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);

if (!$cycleId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Siklus tidak valid.'];
    header("Location: cycle_validation.php"); // Diarahkan ke halaman Riwayat Siklus
    exit;
}
?>
<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Detail Siklus: <span id="cycleNumberDisplay">Memuat...</span></h2>
        <a href="cycle_validation.php" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Kembali ke Riwayat Siklus</a>
    </div>

    <div id="ajax-message-container"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <div class="card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Informasi Siklus</h3>
                <dl id="cycleInfoDl" class="detail-grid detail-grid-improved text-sm"></dl>
            </div>
             <div class="card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Status Siklus</h3>
                <div id="statusDisplayContainer">Memuat status...</div>
            </div>
        </div>

        <div class="lg:col-span-2 card">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Muatan dalam Siklus Ini</h3>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead><tr class="bg-gray-100 text-gray-600 uppercase text-sm"><th class="py-2 px-4 text-left">Nama Muatan</th><th class="py-2 px-4 text-left">Tujuan</th><th class="py-2 px-4 text-left">Dibuat Pada</th><th class="py-2 px-4 text-center">Status Muatan</th></tr></thead>
                    <tbody id="loadsInCycleTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleId = <?php echo $cycleId; ?>;

    function fetchCycleDetails() {
        fetch(`php_scripts/get_cycle_details.php?cycle_id=${cycleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderCycleDetails(data.cycle);
                    renderLoadsTable(data.loads);
                } else {
                    document.getElementById('ajax-message-container').innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                }
            });
    }

    function renderCycleDetails(cycle) {
        document.getElementById('cycleNumberDisplay').textContent = escapeHtml(cycle.cycle_number);
        const statusInfo = getUniversalStatusBadge(cycle.status);
        
        // PERUBAHAN: Mengadopsi format detail yang sama dengan load_detail.php
        document.getElementById('cycleInfoDl').innerHTML = `
            <dt><span class="material-icons">tag</span>Nomor Siklus:</dt><dd class="font-mono">${escapeHtml(cycle.cycle_number)}</dd>
            <dt><span class="material-icons">local_convenience_store</span>Mesin:</dt><dd>${escapeHtml(cycle.machine_name)}</dd>
            <dt><span class="material-icons">engineering</span>Operator:</dt><dd>${escapeHtml(cycle.operator_name || 'N/A')}</dd>
            <dt><span class="material-icons">schedule</span>Dijalankan:</dt><dd>${new Date(cycle.cycle_date).toLocaleString('id-ID')}</dd>
            ${cycle.notes ? `<dt><span class="material-icons">notes</span>Catatan:</dt><dd class="whitespace-pre-wrap">${escapeHtml(cycle.notes)}</dd>` : ''}
        `;

        let statusHtml = '';
        if (cycle.status === 'completed') {
            statusHtml = `<div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4 text-center"><span class="material-icons text-3xl">task_alt</span><p class="font-bold mt-2">Siklus Telah Selesai (Lulus)</p></div>`;
        } else if (cycle.status === 'failed') {
            statusHtml = `<div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4 text-center"><span class="material-icons text-3xl">error</span><p class="font-bold mt-2">Siklus Gagal</p></div>`;
        }
        document.getElementById('statusDisplayContainer').innerHTML = statusHtml;
    }

    function renderLoadsTable(loads) {
        const tableBody = document.getElementById('loadsInCycleTableBody');
        tableBody.innerHTML = '';
        if (loads.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada muatan dalam siklus ini.</td></tr>';
            return;
        }
        loads.forEach(load => {
            const loadStatusInfo = getUniversalStatusBadge(load.status);
            const row = `<tr class="border-b hover:bg-gray-100 clickable-row" data-href="load_detail.php?load_id=${load.load_id}">
                            <td class="py-3 px-4">${escapeHtml(load.load_name)}</td>
                            <td class="py-3 px-4">${escapeHtml(load.destination_department_name || 'Stok Umum')}</td>
                            <td class="py-3 px-4">${new Date(load.created_at).toLocaleString('id-ID')}</td>
                            <td class="py-3 px-4 text-center"><span class="status-badge ${loadStatusInfo.class}">${loadStatusInfo.text}</span></td>
                         </tr>`;
            tableBody.innerHTML += row;
        });
    }
    
    document.getElementById('loadsInCycleTableBody').addEventListener('click', function(e) {
        const row = e.target.closest('tr.clickable-row');
        if (row) { window.location.href = row.dataset.href; }
    });
    
    function getUniversalStatusBadge(status) { const map = { 'completed': { text: 'Selesai (Lulus)', class: 'bg-green-100 text-green-800' }, 'selesai': { text: 'Selesai (Lulus)', class: 'bg-green-100 text-green-800' }, 'failed': { text: 'Gagal', class: 'bg-red-100 text-red-800' }, 'gagal': { text: 'Gagal', class: 'bg-red-100 text-red-800' }}; return map[status.toLowerCase()] || { text: status, class: 'bg-gray-200 text-gray-800' }; }
    function escapeHtml(str) { return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

    fetchCycleDetails();
});
</script>

<?php
require_once 'footer.php';
?>