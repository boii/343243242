<?php
/**
 * Label History Page (AJAX Powered)
 *
 * This version is a complete refactor to use an AJAX-powered table for a modern,
 * responsive, and consistent user experience, matching other management pages.
 * It now handles the 'voided' status and provides contextual action icons.
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

$pageTitle = "Riwayat Label";
require_once 'header.php'; // Includes session check, CSRF token ($csrfToken), $app_settings etc.

// --- Fetch Master Data for Filter Dropdowns ---
$activeDepartments = [];
$dbErrorMessage = '';
$conn_filter = connectToDatabase();
if ($conn_filter) {
    $sqlDepts = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name ASC";
    if ($result = $conn_filter->query($sqlDepts)) {
        while ($row = $result->fetch_assoc()) $activeDepartments[] = $row;
    } else {
        $dbErrorMessage = "Gagal memuat data departemen untuk filter.";
    }
    $conn_filter->close();
} else {
    $dbErrorMessage = "Koneksi database gagal.";
}

// Get filter values from URL for sticky form
$searchQuery = trim($_GET['search_query'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$itemTypeFilter = trim($_GET['item_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

?>
<main class="container mx-auto px-6 py-8">
    <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
        <h2 class="text-2xl font-semibold text-gray-700">Riwayat Label</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Dashboard
        </a>
    </div>

    <?php if ($dbErrorMessage): ?><div class="alert alert-danger"><span class="material-icons">error</span><?php echo htmlspecialchars($dbErrorMessage); ?></div><?php endif; ?>

    <section id="label-list" class="card overflow-x-auto">
         <form id="filterForm" method="GET">
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-700">Daftar Label Tercatat</h3>
                    <p class="text-sm text-gray-600 mt-1">Filter akan berjalan otomatis setelah Anda berhenti berinteraksi.</p>
                </div>
                <div class="flex space-x-2 w-full md:w-auto">
                    <button type="button" id="exportExcelBtn" class="btn bg-green-600 text-white hover:bg-green-700 flex-grow md:flex-grow-0">
                        <span class="material-icons mr-2">grid_on</span>Ekspor ke Excel
                    </button>
                    <button type="button" id="exportCsvBtn" class="btn btn-success flex-grow md:flex-grow-0">
                        <span class="material-icons mr-2">download</span>Ekspor ke CSV
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                <div class="lg:col-span-2">
                    <label for="searchQuery" class="form-label text-sm">Cari (ID, Nama Label, Muatan)</label>
                    <input type="text" id="searchQuery" name="search_query" class="form-input" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div>
                    <label for="itemTypeFilter" class="form-label text-sm">Tipe Item</label>
                    <select id="itemTypeFilter" name="item_type" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="instrument" <?php echo ($itemTypeFilter === 'instrument') ? 'selected' : ''; ?>>Instrumen</option>
                        <option value="set" <?php echo ($itemTypeFilter === 'set') ? 'selected' : ''; ?>>Set</option>
                    </select>
                </div>
                <div>
                    <label for="statusFilter" class="form-label text-sm">Status</label>
                    <select id="statusFilter" name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="active" <?php echo ($statusFilter === 'active') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="used" <?php echo ($statusFilter === 'used') ? 'selected' : ''; ?>>Digunakan</option>
                        <option value="used_accepted" <?php echo ($statusFilter === 'used_accepted') ? 'selected' : ''; ?>>Penggunaan Diterima</option>
                        <option value="expired" <?php echo ($statusFilter === 'expired') ? 'selected' : ''; ?>>Kedaluwarsa</option>
                        <option value="pending_validation" <?php echo ($statusFilter === 'pending_validation') ? 'selected' : ''; ?>>Pending Validasi</option>
                        <option value="recalled" <?php echo ($statusFilter === 'recalled') ? 'selected' : ''; ?>>Ditarik Kembali</option>
                        <option value="voided" <?php echo ($statusFilter === 'voided') ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>
                 <div>
                    <label for="departmentFilter" class="form-label text-sm">Tujuan</label>
                    <select id="departmentFilter" name="department_id" class="form-select">
                        <option value="">Semua Tujuan</option>
                        <?php foreach ($activeDepartments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" <?php echo ($departmentFilter == $dept['department_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-full">
                     <label for="dateStart" class="form-label text-sm">Rentang Tanggal Dibuat</label>
                     <div class="flex flex-col sm:flex-row gap-4">
                         <input type="date" id="dateStart" name="date_start" class="form-input" title="Tanggal Mulai" value="<?php echo htmlspecialchars($dateStart); ?>">
                         <input type="date" id="dateEnd" name="date_end" class="form-input" title="Tanggal Akhir" value="<?php echo htmlspecialchars($dateEnd); ?>">
                         <button type="button" id="resetFilters" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300"><span class="material-icons mr-2">refresh</span>Reset</button>
                     </div>
                </div>
            </div>
        </form>

        <div class="mt-4">
            <table class="w-full table-auto" id="labelHistoryTable">
                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <tr>
                        <th class="py-3 px-6 text-left">ID Label & Item</th>
                        <th class="py-3 px-6 text-left">Asal Muatan</th>
                        <th class="py-3 px-6 text-left">Dibuat & Kedaluwarsa</th>
                        <th class="py-3 px-6 text-left">Tujuan Departemen</th>
                        <th class="py-3 px-6 text-center">Status</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="labelsTableBody" class="text-gray-600 text-sm font-light">
                </tbody>
            </table>
        </div>
        <div id="paginationContainer" class="mt-4 flex justify-center items-center space-x-1 pagination"></div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let debounceTimer;
    const filterForm = document.getElementById('filterForm');
    const tableBody = document.getElementById('labelsTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const resetFiltersBtn = document.getElementById('resetFilters');

    function getFilterValues() {
        return new URLSearchParams(new FormData(filterForm)).toString();
    }

    function fetchLabels(page = 1) {
        const filters = getFilterValues();
        history.pushState(null, '', `?page=${page}&${filters}`);
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat riwayat label...</td></tr>';

        const url = `php_scripts/get_labels_data.php?page=${page}&${filters}`;
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

    function renderTable(labels) {
        tableBody.innerHTML = '';
        if (labels.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-6 px-6 text-gray-600">Tidak ada riwayat ditemukan untuk filter yang dipilih. <a href="label_history.php" class="text-blue-600 hover:underline">Reset Filter</a></td></tr>`;
            return;
        }

        labels.forEach(label => {
            let statusBadge = `<span class="status-badge ${escapeHtml(label.status_class)}">${escapeHtml(label.status_text)}</span>`;

            // --- PERUBAHAN BARU DIMULAI DI SINI ---
            if (label.status === 'used_accepted') {
                if (label.return_condition === 'damaged') {
                    statusBadge += `<span class="material-icons text-yellow-600 ml-1 text-base" title="Diterima dengan catatan masalah/kerusakan">warning</span>`;
                } else if (label.return_condition === 'good') {
                    statusBadge += `<span class="material-icons text-green-600 ml-1 text-base" title="Diterima dalam kondisi baik">check_circle</span>`;
                }
            }
            // --- PERUBAHAN BARU SELESAI ---

            const createdDate = new Date(label.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            const expiryDate = new Date(label.expiry_date).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

            const statusToClassMap = {
                'active': 'tr-status-tersedia', 'used': 'tr-status-sterilisasi', 'used_accepted': 'tr-status-selesai',
                'expired': 'tr-status-gagal', 'recalled': 'tr-status-gagal', 'voided': 'tr-status-gagal',
                'pending_validation': 'tr-status-menunggu_validasi'
            };
            const rowStatusClass = statusToClassMap[label.status] || 'tr-status-default';

            let modifiedIcon = '';
            if (label.item_type === 'set' && label.is_modified) {
                modifiedIcon = `<span class="material-icons text-indigo-500 text-base ml-1" title="Definisi set ini telah berubah sejak label dicetak. Klik untuk melihat detail perubahan.">drive_file_rename_outline</span>`;
            }

            const isPrintable = ['active', 'used'].includes(label.status);
            let actionButton = '';

            if (isPrintable) {
                actionButton = `
                    <a href="print_router.php?label_uid=${label.label_unique_id}" target="_blank" class="btn-icon btn-icon-action" title="Cetak Ulang Label">
                        <span class="material-icons">print</span>
                    </a>`;
            } else {
                actionButton = `
                    <div class="btn-icon opacity-50 cursor-not-allowed" title="Label dengan status ini tidak dapat dicetak">
                        <span class="material-icons">block</span>
                    </div>`;
            }

            const row = `
                <tr class="border-b border-gray-200 hover:bg-gray-100 table-status-indicator clickable-row ${rowStatusClass}" data-href="verify_label.php?uid=${label.label_unique_id}">
                    <td class="py-3 px-6 text-left">
                        <div class="font-mono font-semibold text-gray-800">${escapeHtml(label.label_unique_id)}</div>
                        <div class="text-xs text-gray-500 flex items-center">${escapeHtml(label.label_title || 'N/A')} ${modifiedIcon}</div>
                    </td>
                    <td class="py-3 px-6 text-left">${escapeHtml(label.load_name || '-')}</td>
                    <td class="py-3 px-6 text-left">
                        <div>Dibuat: ${createdDate}</div>
                        <div class="text-xs text-gray-500">Kedaluwarsa: ${expiryDate}</div>
                    </td>
                    <td class="py-3 px-6 text-left">${escapeHtml(label.destination_department_name || 'Stok Umum')}</td>
                    <td class="py-3 px-6 text-center"><div class="flex items-center justify-center">${statusBadge}</div></td>
                    <td class="py-3 px-6 text-center">
                        <div class="flex item-center justify-center space-x-1">
                            ${actionButton}
                        </div>
                    </td>
                </tr>`;
            tableBody.innerHTML += row;
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
                fetchLabels(i);
            });
            paginationContainer.appendChild(pageLink);
        }
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
    }

    const inputs = filterForm.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchLabels(1);
            }, 500);
        });
    });

    resetFiltersBtn.addEventListener('click', () => {
        filterForm.reset();
        fetchLabels(1);
    });

    exportCsvBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const currentFilters = getFilterValues();
        window.location.href = `php_scripts/export_labels_csv.php?${currentFilters}`;
    });

    exportExcelBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const currentFilters = getFilterValues();
        window.location.href = `php_scripts/export_labels_excel.php?${currentFilters}`;
    });

    tableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr.clickable-row');
        if (row && !e.target.closest('a, div.btn-icon')) {
            window.location.href = row.dataset.href;
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    fetchLabels(pageFromUrl);
});
</script>
<?php
require_once 'footer.php';
?>
