<?php
/**
 * Manage Sets Page (Full UI/UX Revamp)
 *
 * This version uses a dedicated page for creation, an AJAX-powered table for a
 * modern UX, and makes the entire table row clickable. It now includes
 * creator and last update information in the table.
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

$pageTitle = "Kelola Set Instrumen";
require_once 'header.php';

// Authorization check for Admins, Supervisors, and authorized Staff
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');
if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageSets))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengelola set instrumen.'];
    header("Location: index.php");
    exit;
}

$searchQuery = trim($_GET['search'] ?? '');
?>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Kelola Set Instrumen</h2>
        <a href="index.php" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Dashboard</a>
    </div>
    
    <section class="card overflow-x-auto">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-700">Daftar Set</h3>
                <p class="text-sm text-gray-600 mt-1">Klik pada baris untuk melihat detail. Filter akan berjalan otomatis.</p>
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <a href="set_create.php" class="btn btn-primary flex-grow md:flex-grow-0">
                    <span class="material-icons mr-2">add</span>Tambah Set Baru
                </a>
            </div>
        </div>
        
        <form id="filterForm" method="GET">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                <div class="lg:col-span-4">
                    <label for="searchSetInput" class="form-label text-sm">Cari Nama atau Kode Set</label>
                    <div class="flex gap-2">
                        <input type="text" id="searchSetInput" name="search" class="form-input" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="button" id="resetSearch" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300">
                            <span class="material-icons mr-2">refresh</span>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="mt-4">
            <table class="w-full table-auto" id="setsTable">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Nama Set</th>
                        <th class="py-3 px-6 text-left">Kode</th>
                        <th class="py-3 px-6 text-center">Jumlah Item</th>
                        <th class="py-3 px-6 text-left">Dibuat Oleh</th>
                        <th class="py-3 px-6 text-left">Terakhir Diperbarui</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="setsTableBody" class="text-gray-600 text-sm font-light">
                </tbody>
            </table>
        </div>
        <div id="paginationContainer" class="mt-4 flex justify-center items-center space-x-1 pagination"></div>
    </section>
</main>

<div id="deleteConfirmationModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="text-lg font-bold mb-2">Konfirmasi Penghapusan</h3>
        <p class="mb-4">Apakah Anda yakin ingin menghapus set <span id="deleteItemName" class="font-bold text-red-700"></span>? Tindakan ini tidak dapat diurungkan.</p>
        <div class="flex justify-center gap-4">
            <button id="cancelDeleteBtn" class="btn btn-secondary">Batal</button>
            <button id="confirmDeleteBtn" class="btn bg-red-600 text-white hover:bg-red-700">Hapus</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemToDelete = { id: null };
    let debounceTimer;

    const filterForm = document.getElementById('filterForm');
    const searchSetInput = document.getElementById('searchSetInput');
    const tableBody = document.getElementById('setsTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const resetBtn = document.getElementById('resetSearch');
    
    const deleteModal = document.getElementById('deleteConfirmationModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    function fetchSets(page = 1) {
        const search = searchSetInput.value;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('page', page);
        urlParams.set('search', search);
        history.pushState(null, '', '?' + urlParams.toString());
        
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat data...</td></tr>';
        
        const url = `php_scripts/get_sets_data.php?page=${page}&search=${encodeURIComponent(search)}`;
        
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

    function renderTable(sets) {
        tableBody.innerHTML = '';
        if (sets.length === 0) {
            const isFiltering = searchSetInput.value.trim() !== '';
            let emptyMessage = isFiltering 
                ? `Tidak ada set ditemukan untuk pencarian Anda. <a href="manage_sets.php" class="text-blue-600 hover:underline">Reset Pencarian?</a>`
                : `Belum ada set instrumen yang dibuat.`;
            
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-6 px-6 text-gray-600">${emptyMessage}</td></tr>`;
            return;
        }
        
        sets.forEach(set => {
            // Perubahan: Format data tanggal dan nama pembuat
            const updatedDate = new Date(set.updated_at).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            const creatorName = escapeHtml(set.creator_name || 'N/A');

            tableBody.innerHTML += `
                <tr class="border-b border-gray-200 hover:bg-gray-100 clickable-row table-status-indicator tr-status-default" data-href="set_detail.php?set_id=${set.set_id}">
                    <td class="py-3 px-6 text-left font-semibold text-gray-800">${escapeHtml(set.set_name)}</td>
                    <td class="py-3 px-6 text-left font-mono">${escapeHtml(set.set_code) || '-'}</td>
                    <td class="py-3 px-6 text-center">${set.item_count}</td>
                    <td class="py-3 px-6 text-left">${creatorName}</td>
                    <td class="py-3 px-6 text-left">${updatedDate}</td>
                    <td class="py-3 px-6 text-center">
                        <div class="flex item-center justify-center">
                            <a href="set_edit.php?set_id=${set.set_id}" class="btn-icon btn-icon-edit" title="Edit"><span class="material-icons">edit</span></a>
                            <button type="button" class="btn-icon btn-icon-delete delete-btn" data-id="${set.set_id}" data-name="${escapeHtml(set.set_name)}" title="Hapus"><span class="material-icons">delete</span></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function renderPagination(pagination) {
        paginationContainer.innerHTML = '';
        if (pagination.totalPages <= 1) return;
        
        const urlParams = new URLSearchParams(window.location.search);
        for (let i = 1; i <= pagination.totalPages; i++) {
            urlParams.set('page', i);
            const pageLink = document.createElement('a');
            pageLink.href = '?' + urlParams.toString();
            pageLink.textContent = i;
            if (i === pagination.currentPage) {
                pageLink.classList.add('active');
            }
            pageLink.addEventListener('click', function(e) {
                e.preventDefault();
                fetchSets(i);
            });
            paginationContainer.appendChild(pageLink);
        }
    }
    
    searchSetInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchSets(1);
        }, 500);
    });

    resetBtn.addEventListener('click', () => {
        searchSetInput.value = '';
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('search');
        urlParams.set('page', 1);
        history.pushState(null, '', '?' + urlParams.toString());
        fetchSets(1);
    });

    tableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr.clickable-row');
        if (row && !e.target.closest('a, button')) {
            window.location.href = row.dataset.href;
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            e.stopPropagation();
            itemToDelete.id = deleteBtn.dataset.id;
            document.getElementById('deleteItemName').textContent = `'${deleteBtn.dataset.name}'`;
            deleteModal.classList.add('active');
        }
    });

    confirmDeleteBtn.addEventListener('click', () => {
        if (itemToDelete.id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'php_scripts/set_delete.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="set_id_to_delete" value="${itemToDelete.id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
    deleteModal.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.classList.remove('active'); });

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    const searchFromUrl = urlParams.get('search') || '';
    searchSetInput.value = searchFromUrl;
    fetchSets(pageFromUrl);
});
</script>

<?php
require_once 'footer.php';
?>