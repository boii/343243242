<?php
/**
 * Manage Sterilization Loads Page (Elegant & Compact UI/UX)
 *
 * This version removes the session selection from the creation modal,
 * simplifying the workflow.
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

$pageTitle = "Manajemen Muatan Sterilisasi";
require_once 'header.php'; // Includes session check, CSRF token, etc.

// Fetch all master data needed for create AND filter dropdowns
$activeMachines = [];
$activeDepartments = [];
$dbErrorMessage = '';

$conn_loads = connectToDatabase();
if ($conn_loads) {
    // Fetch all active machines
    $sqlMachines = "SELECT machine_id, machine_name FROM machines WHERE is_active = 1 ORDER BY machine_name ASC";
    if ($result = $conn_loads->query($sqlMachines)) while($row = $result->fetch_assoc()) $activeMachines[] = $row;
    else $dbErrorMessage .= " Gagal memuat data mesin. ";

    // Fetch all active departments
    $sqlDepts = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name ASC";
    if ($result = $conn_loads->query($sqlDepts)) while($row = $result->fetch_assoc()) $activeDepartments[] = $row;
    else $dbErrorMessage .= " Gagal memuat data departemen. ";

    $conn_loads->close();
} else {
    $dbErrorMessage = "Koneksi ke database gagal.";
}

// Get filter values from URL for sticky form
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$machineFilter = trim($_GET['machine_id'] ?? '');
$departmentFilter = trim($_GET['department_id'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');

?>
<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Manajemen Muatan</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Dashboard
        </a>
    </div>

    <?php if ($dbErrorMessage): ?><div class="alert alert-danger"><span class="material-icons">error_outline</span><?php echo htmlspecialchars($dbErrorMessage); ?></div><?php endif; ?>

    <section class="card overflow-x-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-700">Daftar Muatan</h3>
                <p class="text-sm text-gray-600 mt-1">Klik pada baris untuk melihat detail. Filter akan berjalan otomatis.</p>
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <button type="button" id="exportCsvBtn" class="btn btn-success flex-grow md:flex-grow-0">
                    <span class="material-icons mr-2">download</span>Ekspor ke CSV
                </button>
                <button type="button" id="openAddLoadModalBtn" class="btn btn-primary flex-grow md:flex-grow-0">
                    <span class="material-icons mr-2">add</span>Buat Muatan Baru
                </button>
            </div>
        </div>
        
        <form id="filterForm" action="manage_loads.php" method="GET">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                <div class="lg:col-span-1">
                    <label for="search_query" class="form-label text-sm">Cari</label>
                    <input type="text" id="search_query" name="search" class="form-input" placeholder="Nama, Mesin, Pembuat..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div>
                    <label for="status" class="form-label text-sm">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="persiapan" <?php echo ($statusFilter === 'persiapan') ? 'selected' : ''; ?>>Persiapan</option>
                        <option value="menunggu_validasi" <?php echo ($statusFilter === 'menunggu_validasi') ? 'selected' : ''; ?>>Menunggu Validasi</option>
                        <option value="selesai" <?php echo ($statusFilter === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                        <option value="gagal" <?php echo ($statusFilter === 'gagal') ? 'selected' : ''; ?>>Gagal</option>
                    </select>
                </div>
                <div>
                    <label for="filter_machine_id" class="form-label text-sm">Mesin</label>
                    <select id="filter_machine_id" name="machine_id" class="form-select">
                        <option value="">Semua Mesin</option>
                        <?php foreach($activeMachines as $machine): ?>
                            <option value="<?php echo $machine['machine_id']; ?>" <?php echo ($machineFilter == $machine['machine_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($machine['machine_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_department_id" class="form-label text-sm">Tujuan</label>
                    <select id="filter_department_id" name="department_id" class="form-select">
                        <option value="">Semua Tujuan</option>
                        <?php foreach($activeDepartments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" <?php echo ($departmentFilter == $dept['department_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-4">
                    <label class="form-label text-sm">Rentang Tanggal Dibuat</label>
                    <div class="flex flex-col sm:flex-row gap-4">
                         <input type="date" id="date_start" name="date_start" class="form-input" title="Tanggal Mulai" value="<?php echo htmlspecialchars($dateStart); ?>">
                         <input type="date" id="date_end" name="date_end" class="form-input" title="Tanggal Akhir" value="<?php echo htmlspecialchars($dateEnd); ?>">
                         <button type="button" id="resetFilters" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300"><span class="material-icons mr-2">refresh</span>Reset</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="mt-4">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Nama Muatan & Pembuat</th>
                        <th class="py-3 px-6 text-left">Mesin</th>
                        <th class="py-3 px-6 text-left">Siklus & Tujuan</th>
                        <th class="py-3 px-6 text-center">Jml Item</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="loadsTableBody" class="text-gray-600 text-sm font-light"></tbody>
            </table>
        </div>
        <div id="paginationContainer" class="mt-4 flex justify-center items-center space-x-1 pagination"></div>
    </section>
</main>

<div id="addLoadModal" class="modal-overlay">
    <div class="modal-content max-w-2xl">
        <h3 class="text-xl font-semibold text-gray-700 mb-4">Buat Muatan Baru</h3>
        <form action="php_scripts/load_add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end text-left">
                <div>
                    <label for="machine_id" class="form-label">Pilih Mesin</label>
                    <select id="machine_id" name="machine_id" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Mesin --</option>
                        <?php foreach($activeMachines as $machine): ?>
                            <option value="<?php echo $machine['machine_id']; ?>"><?php echo htmlspecialchars($machine['machine_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="destination_department_id" class="form-label">Departemen Tujuan</label>
                    <select id="destination_department_id" name="destination_department_id" class="form-select">
                         <option value="">-- Stok Umum (Default) --</option>
                         <?php foreach($activeDepartments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                         <?php endforeach; ?>
                    </select>
                </div>
                 <div class="md:col-span-2">
                    <label for="notes" class="form-label">Catatan (Opsional)</label>
                    <input type="text" id="notes" name="notes" class="form-input" placeholder="Contoh: Alat CITO">
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-6">
                <button type="button" id="cancelAddLoadBtn" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons mr-2">add</span>Buat Muatan & Lanjutkan
                </button>
            </div>
        </form>
    </div>
</div>

<div id="deleteLoadModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="text-lg font-bold mb-2">Konfirmasi Penghapusan</h3>
        <p class="mb-4">Apakah Anda yakin ingin menghapus muatan <strong id="deleteLoadName" class="text-red-700"></strong>? <br><small>Aksi ini hanya dapat dilakukan pada muatan yang berstatus "Persiapan".</small></p>
        <form id="deleteLoadForm" action="php_scripts/load_delete.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="load_id_to_delete" id="loadIdToDelete">
            <div class="flex justify-center gap-4 mt-6">
                <button type="button" id="cancelDeleteBtn" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn bg-red-600 text-white hover:bg-red-700">Ya, Hapus Muatan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let debounceTimer;
    // --- Referensi Elemen ---
    const filterForm = document.getElementById('filterForm');
    const tableBody = document.getElementById('loadsTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const resetFiltersBtn = document.getElementById('resetFilters');

    // Modal untuk Add
    const addLoadModal = document.getElementById('addLoadModal');
    const openAddLoadModalBtn = document.getElementById('openAddLoadModalBtn');
    const cancelAddLoadBtn = document.getElementById('cancelAddLoadBtn');

    // Modal untuk Delete
    const deleteLoadModal = document.getElementById('deleteLoadModal');
    const deleteLoadNameSpan = document.getElementById('deleteLoadName');
    const loadIdToDeleteInput = document.getElementById('loadIdToDelete');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    // --- Fungsi ---
    function getFilterValues() {
        const formData = new FormData(filterForm);
        return new URLSearchParams(formData).toString();
    }

    function fetchLoads(page = 1) {
        const filters = getFilterValues();
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat data muatan...</td></tr>';
        
        const url = `php_scripts/get_loads_data.php?page=${page}&${filters}`;
        
        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject('Network error'))
            .then(result => {
                if (result.success) {
                    renderTable(result.data);
                    renderPagination(result.pagination);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Error: ${result.error}</td></tr>`;
                }
            })
            .catch(error => {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Gagal mengambil data.</td></tr>`;
            });
    }
    
    function renderTable(loads) {
        tableBody.innerHTML = '';
        if (loads.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-6 px-6 text-gray-600">Tidak ada muatan ditemukan untuk filter yang dipilih. <a href="manage_loads.php" class="text-blue-600 hover:underline">Reset Filter</a></td></tr>`;
            return;
        }
        
        loads.forEach(load => {
            const statusBadge = `<span class="status-badge ${escapeHtml(load.status_class)}">${escapeHtml(load.status_text)}</span>`;
            
            let actionButtons = '';
            if (load.status === 'persiapan') {
                actionButtons += `<button type="button" class="btn-icon btn-icon-delete delete-load-btn" data-load-id="${load.load_id}" data-load-name="${escapeHtml(load.load_name)}" title="Hapus Muatan"><span class="material-icons">delete</span></button>`;
            }
            
            const notesIcon = load.notes ? `<span class="material-icons text-gray-400 ml-1" title="${escapeHtml(load.notes)}">info</span>` : '';
            const creatorInfo = `<div class="text-xs text-gray-500">oleh ${escapeHtml(load.creator_name || 'N/A')} pada ${new Date(load.created_at).toLocaleString('id-ID', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' })}</div>`;
            
            tableBody.innerHTML += `
                <tr class="border-b border-gray-200 hover:bg-gray-100 table-status-indicator clickable-row ${escapeHtml(load.row_status_class)}" data-href="load_detail.php?load_id=${load.load_id}">
                    <td class="py-3 px-6 text-left">
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-800">${escapeHtml(load.load_name)}</span>
                            ${notesIcon}
                        </div>
                        ${creatorInfo}
                    </td>
                    <td class="py-3 px-6 text-left">${escapeHtml(load.machine_name || 'N/A')}</td>
                    <td class="py-3 px-6 text-left">${escapeHtml(load.destination_department_name || 'Stok Umum')}<br><span class="text-xs text-gray-500">${escapeHtml(load.cycle_number || '-')}</span></td>
                    <td class="py-3 px-6 text-center">${load.item_count}</td>
                    <td class="py-3 px-6 text-center">${statusBadge}</td>
                    <td class="py-3 px-6 text-center"><div class="flex items-center justify-center space-x-1">${actionButtons}</div></td>
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
            if (i === pagination.currentPage) {
                pageLink.classList.add('active');
            }
            pageLink.addEventListener('click', (e) => {
                e.preventDefault();
                history.pushState(null, '', `?page=${i}&${filterParams}`);
                fetchLoads(i);
            });
            paginationContainer.appendChild(pageLink);
        }
    }

    function escapeHtml(str) { 
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); 
    }

    // --- Event Listeners ---
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const currentFilters = getFilterValues();
            const exportUrl = `php_scripts/export_loads_csv.php?${currentFilters}`;
            window.location.href = exportUrl;
        });
    }

    const inputs = filterForm.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const newUrl = window.location.pathname + '?' + getFilterValues();
                history.pushState(null, '', newUrl);
                fetchLoads(1);
            }, 500);
        });
    });

    resetFiltersBtn.addEventListener('click', function() {
        filterForm.reset();
        const newUrl = window.location.pathname;
        history.pushState(null, '', newUrl);
        fetchLoads(1);
    });

    tableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr.clickable-row');
        // Pastikan klik tidak berasal dari tombol aksi atau ikon info
        if (row && !e.target.closest('button') && !e.target.closest('.material-icons[title]')) {
            window.location.href = row.dataset.href;
        }
    });

    // Modal Add
    openAddLoadModalBtn.addEventListener('click', () => addLoadModal.classList.add('active'));
    cancelAddLoadBtn.addEventListener('click', () => addLoadModal.classList.remove('active'));
    addLoadModal.addEventListener('click', e => { if (e.target === addLoadModal) addLoadModal.classList.remove('active'); });

    // Modal Delete
    tableBody.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-load-btn');
        if (deleteBtn) {
            e.stopPropagation(); // Hentikan event agar tidak memicu klik pada baris
            deleteLoadNameSpan.textContent = deleteBtn.dataset.loadName;
            loadIdToDeleteInput.value = deleteBtn.dataset.loadId;
            deleteLoadModal.classList.add('active');
        }
    });

    cancelDeleteBtn.addEventListener('click', () => deleteLoadModal.classList.remove('active'));
    deleteLoadModal.addEventListener('click', e => { if (e.target === deleteLoadModal) deleteLoadModal.classList.remove('active'); });

    // --- Initial Load ---
    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    fetchLoads(pageFromUrl);
});
</script>

<?php
require_once 'footer.php';
?>