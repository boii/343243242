<?php
/**
 * Reports & Statistics Dashboard (Full UI/UX Revamp)
 *
 * This version transforms the page into an intelligent dashboard featuring
 * KPI stat cards, richer data visualizations, and an actionable, filterable
 * expiry report table.
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

$pageTitle = "Laporan & Statistik";
require_once 'header.php';

// Authorization: Only Admins and Supervisors can view reports
if (!in_array($userRole, ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Laporan & Dasbor Statistik</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Dashboard
        </a>
    </div>

    <section id="kpi-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card">
             <div class="stat-header">
                <div class="icon-container bg-blue-100"><span class="material-icons text-3xl text-blue-600">task_alt</span></div>
                <div><p class="stat-title">Tingkat Keberhasilan Siklus</p><p id="kpi-cycle-success-rate" class="stat-number"><span class="stat-loading"></span></p></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="icon-container bg-green-100"><span class="material-icons text-3xl text-green-600">inventory_2</span></div>
                <div><p class="stat-title">Total Instrumen Tersedia</p><p id="kpi-instruments-available" class="stat-number"><span class="stat-loading"></span></p></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="icon-container bg-yellow-100"><span class="material-icons text-3xl text-yellow-600">pending_actions</span></div>
                <div><p class="stat-title">Item Akan Kedaluwarsa (30 Hari)</p><p id="kpi-expiring-soon" class="stat-number"><span class="stat-loading"></span></p></div>
            </div>
        </div>
        <div class="stat-card">
             <div class="stat-header">
                <div class="icon-container bg-red-100"><span class="material-icons text-3xl text-red-600">error</span></div>
                <div><p class="stat-title">Instrumen Butuh Perhatian</p><p id="kpi-instruments-attention" class="stat-number"><span class="stat-loading"></span></p></div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <div class="lg:col-span-2 card">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Tren Pembuatan Label (30 Hari Terakhir)</h3>
            <div class="chart-container h-80">
                <canvas id="labelCreationChart"></canvas>
            </div>
        </div>
        <div class="lg:col-span-1 card">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Distribusi Status Instrumen</h3>
            <div class="chart-container h-80">
                <canvas id="instrumentStatusChart"></canvas>
            </div>
        </div>
    </section>

    <section class="card">
        <h3 class="text-xl font-semibold text-gray-700 mb-4">Laporan Detail Kedaluwarsa</h3>
        <p class="text-sm text-gray-600 mb-4">Menampilkan semua item aktif yang akan kedaluwarsa atau sudah kedaluwarsa. Gunakan filter untuk mencari.</p>
        <div class="mb-4">
            <input type="text" id="expirySearchInput" class="form-input" placeholder="Cari berdasarkan nama item, ID label, atau lokasi tujuan...">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-auto" id="expiryTable">
                <thead class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                    <tr>
                        <th class="py-3 px-6 text-left cursor-pointer" data-sort="item_name">Nama Item (Label) <span class="material-icons text-sm">unfold_more</span></th>
                        <th class="py-3 px-6 text-left cursor-pointer" data-sort="destination_department_name">Tujuan <span class="material-icons text-sm">unfold_more</span></th>
                        <th class="py-3 px-6 text-center cursor-pointer" data-sort="expiry_date">Tgl Kedaluwarsa <span class="material-icons text-sm">unfold_more</span></th>
                        <th class="py-3 px-6 text-center cursor-pointer" data-sort="days_left">Sisa Waktu <span class="material-icons text-sm">unfold_more</span></th>
                    </tr>
                </thead>
                <tbody id="expiryTableBody" class="text-gray-600 text-sm font-light">
                    <tr><td colspan="4" class="text-center py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allReportData = null;
    let expirySortState = { column: 'days_left', direction: 'asc' };

    const chartContexts = {
        labelCreation: document.getElementById('labelCreationChart')?.getContext('2d'),
        instrumentStatus: document.getElementById('instrumentStatusChart')?.getContext('2d'),
    };
    const chartInstances = {};

    function fetchAndRenderAll() {
        fetch('php_scripts/get_report_data.php')
            .then(response => response.ok ? response.json() : Promise.reject('Failed to load'))
            .then(result => {
                if (result.success) {
                    allReportData = result.data;
                    renderKPIs(allReportData.kpis);
                    renderLabelCreationChart(allReportData.label_creation_trend);
                    renderInstrumentStatusChart(allReportData.instrument_status_distribution);
                    renderExpiryTable(allReportData.expiry_report);
                } else {
                    console.error("Gagal memuat data laporan:", result.error);
                }
            })
            .catch(error => console.error('Error fetching report data:', error));
    }

    function renderKPIs(kpis) {
        document.getElementById('kpi-cycle-success-rate').textContent = `${kpis.cycle_success_rate.toFixed(1)}%`;
        document.getElementById('kpi-instruments-available').textContent = kpis.instruments_available;
        document.getElementById('kpi-expiring-soon').textContent = kpis.items_expiring_soon;
        document.getElementById('kpi-instruments-attention').textContent = kpis.instruments_needing_attention;
    }

    function renderLabelCreationChart(data) {
        if (!chartContexts.labelCreation) return;
        if (chartInstances.labelCreation) chartInstances.labelCreation.destroy();
        
        chartInstances.labelCreation = new Chart(chartContexts.labelCreation, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Label Dibuat',
                    data: data.map(d => ({x: d.creation_date, y: d.count})),
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { type: 'time', time: { unit: 'day', tooltipFormat: 'dd MMM yyyy' } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    function renderInstrumentStatusChart(data) {
        if (!chartContexts.instrumentStatus) return;
        if (chartInstances.instrumentStatus) chartInstances.instrumentStatus.destroy();
        
        chartInstances.instrumentStatus = new Chart(chartContexts.instrumentStatus, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                datasets: [{
                    data: data.map(d => d.count),
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#3b82f6'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
        });
    }
    
    function renderExpiryTable(data, filter = '') {
        const tableBody = document.getElementById('expiryTableBody');
        tableBody.innerHTML = '';
        
        const filteredData = data.filter(item => {
            const searchStr = `${item.item_name} ${item.label_unique_id} ${item.destination_department_name || ''}`.toLowerCase();
            return searchStr.includes(filter.toLowerCase());
        });

        const sortedData = filteredData.sort((a, b) => {
            const valA = a[expirySortState.column];
            const valB = b[expirySortState.column];
            let comparison = 0;
            if (valA > valB) { comparison = 1; } 
            else if (valA < valB) { comparison = -1; }
            return expirySortState.direction === 'desc' ? comparison * -1 : comparison;
        });

        if (sortedData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada item yang cocok dengan filter Anda.</td></tr>';
            return;
        }

        const now = new Date();
        sortedData.forEach(item => {
            const expiryDate = new Date(item.expiry_date);
            const diffTime = expiryDate - now;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let timeLeftText = ''; let rowClass = ''; let textColor = 'text-gray-600';
            if (diffDays < 0) { timeLeftText = `Lewat ${Math.abs(diffDays)} hari`; rowClass = 'bg-red-50'; textColor = 'text-red-700 font-semibold'; } 
            else if (diffDays <= 7) { timeLeftText = `${diffDays} hari lagi`; rowClass = 'bg-yellow-50'; textColor = 'text-yellow-700 font-semibold'; } 
            else { timeLeftText = `${diffDays} hari lagi`; }

            const row = `
                <tr class="${rowClass}">
                    <td class="py-3 px-6 text-left">
                        <a href="verify_label.php?uid=${item.label_unique_id}" class="font-medium text-blue-600 hover:underline">${escapeHtml(item.item_name)}</a>
                        <div class="text-xs text-gray-500 font-mono">${item.label_unique_id}</div>
                    </td>
                    <td class="py-3 px-6 text-left">${escapeHtml(item.destination_department_name || 'Stok Umum')}</td>
                    <td class="py-3 px-6 text-center">${expiryDate.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})}</td>
                    <td class="py-3 px-6 text-center ${textColor}">${timeLeftText}</td>
                </tr>`;
            tableBody.innerHTML += row;
        });
    }

    document.getElementById('expirySearchInput').addEventListener('input', (e) => {
        if (allReportData) {
            renderExpiryTable(allReportData.expiry_report, e.target.value);
        }
    });

    document.getElementById('expiryTable').querySelector('thead').addEventListener('click', (e) => {
        const header = e.target.closest('th');
        if (!header || !header.dataset.sort) return;

        const column = header.dataset.sort;
        if (expirySortState.column === column) {
            expirySortState.direction = expirySortState.direction === 'asc' ? 'desc' : 'asc';
        } else {
            expirySortState.column = column;
            expirySortState.direction = 'asc';
        }
        
        // Update icons
        document.querySelectorAll('#expiryTable thead th span').forEach(span => span.textContent = 'unfold_more');
        const icon = header.querySelector('span');
        icon.textContent = expirySortState.direction === 'asc' ? 'expand_less' : 'expand_more';

        if (allReportData) {
            renderExpiryTable(allReportData.expiry_report, document.getElementById('expirySearchInput').value);
        }
    });

    function escapeHtml(str) { return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

    fetchAndRenderAll();
});
</script>

<?php require_once 'footer.php'; ?>