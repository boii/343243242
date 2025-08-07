<?php
/**
 * Manage Instruments Page (Full UI/UX Revamp)
 *
 * This version implements the new standard layout: creation form in a modal,
 * enhanced filters, clickable data rows with visual indicators, and includes
 * an elegant auto-filled but editable instrument code.
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

$pageTitle = "Kelola Instrumen";
require_once 'header.php';

// --- Authorization Check ---
$userRole = $_SESSION['role'] ?? null;
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');

if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageInstruments))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses manajemen instrumen.'];
    header("Location: index.php");
    exit;
}

// --- Fetch Master Data for Forms & Filters ---
$masterTypes = [];
$masterDepartments = [];
$fetchErrorMessage = '';

$conn = connectToDatabase();
if ($conn) {
    // Fetch all active Instrument Types
    $sqlMasterTypes = "SELECT type_id, type_name FROM instrument_types WHERE is_active = 1 ORDER BY type_name ASC";
    if ($result = $conn->query($sqlMasterTypes)) {
        while ($row = $result->fetch_assoc()) $masterTypes[] = $row;
    } else {
        $fetchErrorMessage .= ' Gagal memuat master tipe. ';
    }
    
    // Fetch all active Departments
    $sqlMasterDepts = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name ASC";
    if ($result = $conn->query($sqlMasterDepts)) {
        while ($row = $result->fetch_assoc()) $masterDepartments[] = $row;
    } else {
        $fetchErrorMessage .= ' Gagal memuat master departemen. ';
    }
    
    $conn->close();
} else {
    $fetchErrorMessage = "Koneksi database gagal.";
}

// Get filter values from URL for sticky form
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

?>
<style>
    .btn-expiry-quick-pick {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #e5e7eb;
        color: #4b5563;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.2s;
        white-space: nowrap;
    }
    .btn-expiry-quick-pick:hover {
        background-color: #d1d5db;
    }
</style>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Kelola Instrumen</h2>
        <a href="index.php" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Dashboard</a>
    </div>

    <?php if ($fetchErrorMessage): ?><div class="alert alert-danger" role="alert"><span class="material-icons">error_outline</span><span><?php echo htmlspecialchars($fetchErrorMessage); ?></span></div><?php endif; ?>

    <section class="card overflow-x-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-700">Daftar Instrumen</h3>
                <p class="text-sm text-gray-600 mt-1">Klik pada baris untuk melihat detail. Filter akan berjalan otomatis.</p>
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <button type="button" id="openAddInstrumentModalBtn" class="btn btn-primary flex-grow md:flex-grow-0">
                    <span class="material-icons mr-2">add</span>Tambah Instrumen Baru
                </button>
            </div>
        </div>

        <form id="filterForm" method="GET">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                <div class="lg:col-span-2">
                    <label for="searchInput" class="form-label text-sm">Cari (Nama, Kode)</label>
                    <input type="text" id="searchInput" name="search" class="form-input" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="lg:col-span-2">
                    <label for="filterStatus" class="form-label text-sm">Status</label>
                    <select id="filterStatus" name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="tersedia" <?php echo ($statusFilter === 'tersedia') ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="sterilisasi" <?php echo ($statusFilter === 'sterilisasi') ? 'selected' : ''; ?>>Sterilisasi</option>
                        <option value="perbaikan" <?php echo ($statusFilter === 'perbaikan') ? 'selected' : ''; ?>>Perbaikan</option>
                        <option value="rusak" <?php echo ($statusFilter === 'rusak') ? 'selected' : ''; ?>>Rusak</option>
                    </select>
                </div>
                 <div class="lg:col-span-2">
                    <label for="filterType" class="form-label text-sm">Tipe</label>
                    <select id="filterType" name="type_id" class="form-select">
                        <option value="">Semua Tipe</option>
                        <?php foreach ($masterTypes as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>" <?php echo ($typeFilter == $type['type_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="filterDepartment" class="form-label text-sm">Departemen</label>
                    <select id="filterDepartment" name="department_id" class="form-select">
                        <option value="">Semua Departemen</option>
                        <?php foreach ($masterDepartments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" <?php echo ($departmentFilter == $dept['department_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-full text-right">
                    <button type="button" id="resetFilters" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300">
                        <span class="material-icons mr-2">refresh</span>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </form>
        <div class="mt-4">
            <table class="w-full table-auto">
                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <tr>
                        <th class="py-3 px-2 text-center w-16">Gambar</th>
                        <th class="py-3 px-6 text-left">Nama Instrumen</th>
                        <th class="py-3 px-6 text-left">Tipe</th>
                        <th class="py-3 px-6 text-left">Departemen</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="instrumentsTableBody" class="text-gray-600 text-sm font-light"></tbody>
            </table>
        </div>
        <div id="paginationContainer" class="mt-4 flex justify-center items-center space-x-1 pagination"></div>
    </section>
</main>

<div id="addInstrumentModal" class="modal-overlay">
    <div class="modal-content max-w-3xl text-left">
        <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">Tambah Instrumen Baru</h3>
        <form id="addInstrumentForm" action="php_scripts/instrument_add.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                <div>
                    <label for="instrumentName" class="form-label">Nama Instrumen <span class="text-red-500">*</span></label>
                    <input type="text" id="instrumentName" name="instrument_name" class="form-input" required>
                </div>
                <div>
                    <label for="instrumentCode" class="form-label">ID/Kode <span class="text-red-500">*</span></label>
                    <input type="text" id="instrumentCode" name="instrument_code" class="form-input" required placeholder="Dibuat otomatis, bisa diubah">
                </div>
                <div>
                    <label for="instrument_type_id" class="form-label">Tipe/Kategori <span class="text-red-500">*</span></label>
                    <select id="instrument_type_id" name="instrument_type_id" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Tipe --</option>
                        <?php foreach ($masterTypes as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="department_id" class="form-label">Departemen/Unit <span class="text-red-500">*</span></label>
                    <select id="department_id" name="department_id" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Departemen --</option>
                        <?php foreach ($masterDepartments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label for="expiry_in_days" class="form-label">Masa Kedaluwarsa Standar (Opsional)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="expiry_in_days" name="expiry_in_days" class="form-input w-24" placeholder="Hari" min="1">
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn-expiry-quick-pick" data-target-input="expiry_in_days" data-days="7">7 Hari</button>
                            <button type="button" class="btn-expiry-quick-pick" data-target-input="expiry_in_days" data-days="30">30 Hari</button>
                            <button type="button" class="btn-expiry-quick-pick" data-target-input="expiry_in_days" data-days="365">1 Tahun</button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Kosongkan untuk menggunakan pengaturan global.</p>
                </div>

                <div class="md:col-span-2">
                    <label for="instrument_image" class="form-label">Gambar Instrumen (Opsional)</label>
                    <input type="file" id="instrument_image" name="instrument_image" class="form-input" accept="image/*">
                    <p class="text-xs text-gray-500 mt-1">Ukuran maks: 100 KB. Format: JPG, PNG, GIF, WebP.</p>
                </div>
                <div class="md:col-span-2">
                    <label for="instrumentNotes" class="form-label">Deskripsi/Catatan (Opsional)</label>
                    <textarea id="instrumentNotes" name="notes" rows="3" class="form-input"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cancelAddInstrumentBtn" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><span class="material-icons mr-2">add</span>Tambah Instrumen</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteInstrumentModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="text-lg font-bold mb-2">Konfirmasi Penghapusan</h3>
        <p class="mb-4">Apakah Anda yakin ingin menghapus instrumen <span id="deleteInstrumentName" class="font-bold text-red-700"></span>? <br><small>Aksi ini tidak dapat diurungkan dan akan gagal jika instrumen masih tergabung dalam set.</small></p>
        <div class="flex justify-center gap-4">
            <button id="cancelDeleteBtn" class="btn btn-secondary">Batal</button>
            <button id="confirmDeleteBtn" class="btn bg-red-600 text-white hover:bg-red-700">Hapus</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let debounceTimer;
    const filterForm = document.getElementById('filterForm');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const tableBody = document.getElementById('instrumentsTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    
    const addInstrumentModal = document.getElementById('addInstrumentModal');
    const openAddInstrumentModalBtn = document.getElementById('openAddInstrumentModalBtn');
    const cancelAddInstrumentBtn = document.getElementById('cancelAddInstrumentBtn');
    const addInstrumentForm = document.getElementById('addInstrumentForm');
    const instrumentCodeInput = document.getElementById('instrumentCode');
    
    const deleteModal = document.getElementById('deleteInstrumentModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let itemToDelete = { id: null };

    function getFilterValues() {
        return new URLSearchParams(new FormData(filterForm)).toString();
    }

    function fetchInstruments(page = 1) {
        const filters = getFilterValues();
        history.pushState(null, '', `?page=${page}&${filters}`);
        
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat data instrumen...</td></tr>';

        const url = `php_scripts/get_instruments_data.php?page=${page}&${filters}`;
        
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
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Gagal mengambil data. Silakan coba lagi.</td></tr>`;
            });
    }

    function renderTable(instruments) {
        tableBody.innerHTML = '';
        if (instruments.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-6 px-6 text-gray-600">Tidak ada instrumen ditemukan untuk filter yang dipilih. <a href="manage_instruments.php" class="text-blue-600 hover:underline">Reset Filter</a></td></tr>`;
            return;
        }

        const userRole = '<?php echo $userRole; ?>';

        instruments.forEach(instrument => {
            const statusBadge = `<span class="status-badge ${escapeHtml(instrument.status_class)}">${escapeHtml(instrument.status_text)}</span>`;
            const imageSrc = instrument.image_filename ? `uploads/instruments/${instrument.image_filename}?t=${new Date().getTime()}` : 'https://placehold.co/100x100/e2e8f0/9ca3af?text=?';
            const thumbnail = `<img src="${imageSrc}" alt="${escapeHtml(instrument.instrument_name)}" class="table-thumbnail">`;
            
            const notesIcon = instrument.notes ? `<span class="material-icons text-gray-400 ml-1 text-sm" title="${escapeHtml(instrument.notes)}">info</span>` : '';

            let deleteButton = '';
            if (userRole === 'admin' || userRole === 'supervisor') {
                deleteButton = `<button type="button" class="btn-icon btn-icon-delete delete-instrument-btn" title="Hapus" data-id="${instrument.instrument_id}" data-name="${escapeHtml(instrument.instrument_name)}"><span class="material-icons">delete</span></button>`;
            }

            const row = `
                <tr class="border-b border-gray-200 hover:bg-gray-100 table-status-indicator clickable-row ${escapeHtml(instrument.row_status_class)}" data-href="instrument_detail.php?instrument_id=${instrument.instrument_id}">
                    <td class="py-2 px-2 text-center">${thumbnail}</td>
                    <td class="py-3 px-6 text-left">
                        <div class="flex items-center">
                           <span class="font-semibold text-gray-800">${escapeHtml(instrument.instrument_name)}</span>
                           ${notesIcon} </div>
                        <div class="text-xs text-gray-500 font-mono">${escapeHtml(instrument.instrument_code) || '-'}</div>
                    </td>
                    <td class="py-3 px-6 text-left">${escapeHtml(instrument.type_name) || '-'}</td>
                    <td class="py-3 px-6 text-left">${escapeHtml(instrument.department_name) || '-'}</td>
                    <td class="py-3 px-6 text-center">${statusBadge}</td>
                    <td class="py-3 px-6 text-center">
                        <div class="flex item-center justify-center space-x-1">
                            <a href="instrument_edit.php?instrument_id=${instrument.instrument_id}" class="btn-icon btn-icon-edit" title="Edit"><span class="material-icons">edit</span></a>
                            ${deleteButton}
                        </div>
                    </td>
                </tr>`;
            tableBody.innerHTML += row;
        });
    }

    function renderPagination(pagination) {
        paginationContainer.innerHTML = '';
        if (!pagination || pagination.totalPages <= 1) return;
        
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
                fetchInstruments(i);
            });
            paginationContainer.appendChild(pageLink);
        }
    }

    function escapeHtml(str) { 
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); 
    }

    // --- Event Listeners ---
    const inputs = filterForm.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const newUrl = window.location.pathname + '?' + getFilterValues();
                history.pushState(null, '', newUrl);
                fetchInstruments(1);
            }, 500);
        });
    });

    resetFiltersBtn.addEventListener('click', () => {
        filterForm.reset();
        const newUrl = window.location.pathname;
        history.pushState(null, '', newUrl);
        fetchInstruments(1);
    });

    tableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr.clickable-row');
        if (row && !e.target.closest('button') && !e.target.closest('a')) {
            window.location.href = row.dataset.href;
        }

        const deleteBtn = e.target.closest('.delete-instrument-btn');
        if (deleteBtn) {
            e.stopPropagation();
            itemToDelete.id = deleteBtn.dataset.id;
            document.getElementById('deleteInstrumentName').textContent = `'${deleteBtn.dataset.name}'`;
            deleteModal.classList.add('active');
        }
    });

    openAddInstrumentModalBtn.addEventListener('click', () => {
        addInstrumentForm.reset();
        // --- Perubahan: Kode dibuat otomatis saat modal dibuka ---
        const timestamp = Date.now().toString();
        const uniquePart = timestamp.substring(timestamp.length - 8);
        instrumentCodeInput.value = `INST-${uniquePart}`;
        addInstrumentModal.classList.add('active');
    });
    
    cancelAddInstrumentBtn.addEventListener('click', () => addInstrumentModal.classList.remove('active'));
    addInstrumentModal.addEventListener('click', e => { if (e.target === addInstrumentModal) addInstrumentModal.classList.remove('active'); });
    
    addInstrumentModal.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-expiry-quick-pick')) {
            const days = e.target.dataset.days;
            const expiryInput = document.getElementById('expiry_in_days');
            if (expiryInput) {
                expiryInput.value = days;
            }
        }
    });
    
    confirmDeleteBtn.addEventListener('click', () => {
        if (itemToDelete.id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'php_scripts/instrument_delete.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="instrument_id_to_delete" value="${itemToDelete.id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });

    cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
    deleteModal.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.classList.remove('active'); });

    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    fetchInstruments(pageFromUrl);
});
</script>
<?php
require_once 'footer.php';
?>