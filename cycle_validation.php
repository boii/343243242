<?php
/**
 * Cycle History Page
 *
 * This version serves as a complete history log of all sterilization cycles,
 * simplified by removing method-related UI elements.
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

$pageTitle = "Riwayat Siklus";
require_once 'header.php';

// Authorization check for admin, supervisor, or authorized staff
$staffCanValidateCycles = (isset($app_settings['staff_can_validate_cycles']) && $app_settings['staff_can_validate_cycles'] === '1');
if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanValidateCycles))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}

// Fetch master data for filter dropdowns
$activeMachines = [];
$dbErrorMessage = '';
$conn_filter = connectToDatabase();
if ($conn_filter) {
    $sqlMachines = "SELECT machine_id, machine_name FROM machines WHERE is_active = 1 ORDER BY machine_name ASC";
    if ($result = $conn_filter->query($sqlMachines)) {
        while ($row = $result->fetch_assoc()) $activeMachines[] = $row;
    } else {
        $dbErrorMessage = "Gagal memuat data mesin untuk filter.";
    }
    $conn_filter->close();
} else {
    $dbErrorMessage = "Koneksi database gagal.";
}

// Get filter values from URL for sticky form
$searchQuery = trim($_GET['search_query'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$machineFilter = trim($_GET['machine_id'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');

?>
<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Riwayat Siklus Sterilisasi</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Dashboard
        </a>
    </div>

    <?php if ($dbErrorMessage): ?><div class="alert alert-danger"><span class="material-icons">error</span><?php echo htmlspecialchars($dbErrorMessage); ?></div><?php endif; ?>

    <section class="card overflow-x-auto">
        <form id="filterForm" method="GET" action="cycle_validation.php">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                 <div>
                    <h3 class="text-xl font-semibold text-gray-700">Daftar Semua Siklus</h3>
                    <p class="text-sm text-gray-600 mt-1">Gunakan filter untuk mencari siklus yang spesifik. Klik pada baris untuk melihat detail.</p>
                </div>
            </div>
             <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                <div class="lg:col-span-2">
                    <label for="search_query" class="form-label text-sm">Cari (No. Siklus, Mesin, Operator)</label>
                    <input type="text" id="search_query" name="search_query" class="form-input" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div>
                    <label for="status" class="form-label text-sm">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="completed" <?php echo ($statusFilter === 'completed') ? 'selected' : ''; ?>>Selesai (Lulus)</option>
                        <option value="failed" <?php echo ($statusFilter === 'failed') ? 'selected' : ''; ?>>Gagal</option>
                    </select>
                </div>
                <div>
                    <label for="machine_id" class="form-label text-sm">Mesin</label>
                    <select id="machine_id" name="machine_id" class="form-select">
                        <option value="">Semua Mesin</option>
                        <?php foreach($activeMachines as $machine): ?>
                            <option value="<?php echo $machine['machine_id']; ?>" <?php echo ($machineFilter == $machine['machine_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($machine['machine_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-full">
                    <label class="form-label text-sm">Rentang Tanggal Siklus</label>
                    <div class="flex flex-col sm:flex-row gap-4">
                         <input type="date" id="date_start" name="date_start" class="form-input" title="Tanggal Mulai" value="<?php echo htmlspecialchars($dateStart); ?>">
                         <input type="date" id="date_end" name="date_end" class="form-input" title="Tanggal Akhir" value="<?php echo htmlspecialchars($dateEnd); ?>">
                         <button type="button" id="resetFilters" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300"><span class="material-icons mr-2">refresh</span>Reset</button>
                    </div>
                </div>
            </div>
        </form>

        <table class="w-full table-auto mt-4">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">Nomor Siklus</th>
                    <th class="py-3 px-6 text-left">Mesin</th>
                    <th class="py-3 px-6 text-left">Waktu Dijalankan</th>
                    <th class="py-3 px-6 text-left">Operator</th>
                    <th class="py-3 px-6 text-center">Status</th>
                </tr>
            </thead>
            <tbody id="cyclesTableBody" class="text-gray-600 text-sm font-light"></tbody>
        </table>
        <div id="paginationContainer" class="mt-4 flex justify-center items-center space-x-1 pagination"></div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let debounceTimer;

    const filterForm = document.getElementById('filterForm');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const tableBody = document.getElementById('cyclesTableBody');
    const paginationContainer = document.getElementById('paginationContainer');

    function getFilterValues() {
        const formData = new FormData(filterForm);
        return new URLSearchParams(formData).toString();
    }

    function fetchCycles(page = 1) {
        const filters = getFilterValues();
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Memuat data siklus...</td></tr>';
        
        const url = `php_scripts/get_cycles_data.php?page=${page}&${filters}`;
        
        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject('Network error'))
            .then(result => {
                if (result.success) {
                    renderTable(result.data);
                    renderPagination(result.pagination);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Error: ${result.error}</td></tr>`;
                }
            })
            .catch(error => {
                tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Gagal mengambil data.</td></tr>`;
            });
    }

    function renderTable(cycles) {
        tableBody.innerHTML = '';
        if (cycles.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-6 px-6 text-gray-600">Tidak ada riwayat siklus ditemukan untuk filter yang dipilih.</td></tr>`;
            return;
        }
        
        cycles.forEach(cycle => {
            const cycleDate = new Date(cycle.cycle_date).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            const statusBadge = `<span class="status-badge ${escapeHtml(cycle.status_class)}">${escapeHtml(cycle.status_text)}</span>`;
            
            const statusMap = { 'completed': 'tr-status-selesai', 'failed': 'tr-status-gagal', 'default': 'tr-status-default' };
            const rowStatusClass = statusMap[cycle.status] || statusMap['default'];
            
            tableBody.innerHTML += `
                <tr class="border-b border-gray-200 hover:bg-gray-100 table-status-indicator clickable-row ${rowStatusClass}" data-href="cycle_detail.php?cycle_id=${cycle.cycle_id}">
                    <td class="py-3 px-6 text-left font-mono">${escapeHtml(cycle.cycle_number)}</td>
                    <td class="py-3 px-6 text-left">${escapeHtml(cycle.machine_name)}</td>
                    <td class="py-3 px-6 text-left">${cycleDate}</td>
                    <td class="py-3 px-6 text-left">${escapeHtml(cycle.operator_name || 'N/A')}</td>
                    <td class="py-3 px-6 text-center">${statusBadge}</td>
                </tr>
            `;
        });
    }

    function renderPagination(pagination) {
        paginationContainer.innerHTML = '';
        if (pagination.totalPages <= 1) return;
        const filterParams = getFilterValues();
        for (let i = 1; i <= pagination.totalPages; i++) {
            const pageLink = document.createElement('a');
            pageLink.href = `?page=${i}&${filterParams}`;
            pageLink.textContent = i;
            if (i === pagination.currentPage) { pageLink.classList.add('active'); }
            pageLink.addEventListener('click', (e) => { e.preventDefault(); history.pushState(null, '', `?page=${i}&${filterParams}`); fetchCycles(i); });
            paginationContainer.appendChild(pageLink);
        }
    }
    
    resetFiltersBtn.addEventListener('click', function() { filterForm.reset(); const newUrl = window.location.pathname; history.pushState(null, '', newUrl); fetchCycles(1); });
    const inputs = filterForm.querySelectorAll('input, select');
    inputs.forEach(input => { input.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { const newUrl = window.location.pathname + '?' + getFilterValues(); history.pushState(null, '', newUrl); fetchCycles(1); }, 500); }); });
    tableBody.addEventListener('click', function(e) { const row = e.target.closest('tr.clickable-row'); if (row) { window.location.href = row.dataset.href; } });
    function escapeHtml(str) { return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&lt;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    fetchCycles(pageFromUrl);
});
</script>

<?php
require_once 'footer.php';
?>