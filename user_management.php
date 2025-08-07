<?php
/**
 * User Management Page (UI/UX Revamp)
 *
 * This version uses a dynamic, AJAX-powered table for a modern and
 * consistent user experience, and moves the creation form into a modal
 * with a more precise and elegant two-column layout.
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

$pageTitle = "Manajemen Pengguna";
require_once 'header.php'; // Includes session check, CSRF token, etc.

// Role-based access control: Admins and Supervisors can access this page
if (!in_array($userRole, ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}

// Get form data for repopulation if there was an error
$formData = $_SESSION['form_data_user_add'] ?? [];
unset($_SESSION['form_data_user_add']);

$searchQuery = trim($_GET['search'] ?? '');
?>
<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Manajemen Pengguna</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Kembali ke Dashboard
        </a>
    </div>
    
    <section id="user-list" class="card overflow-x-auto">
         <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-700">Daftar Pengguna</h3>
                <p class="text-sm text-gray-600 mt-1">Gunakan filter untuk mencari pengguna spesifik. Klik pada baris untuk mengedit.</p>
            </div>
            <button type="button" id="openAddUserModalBtn" class="btn btn-primary w-full md:w-auto">
                <span class="material-icons mr-2">person_add</span>Tambah Pengguna Baru
            </button>
        </div>
        
        <form id="filterForm" method="GET">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                <div class="lg:col-span-4">
                    <label for="userSearchInput" class="form-label text-sm">Cari (Nama, Username, Email)</label>
                    <div class="flex gap-2">
                        <input type="text" id="userSearchInput" name="search" class="form-input" placeholder="Ketik kata kunci..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="button" id="resetUserSearch" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300">
                            <span class="material-icons mr-2">refresh</span>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <div class="mt-4">
            <table class="w-full table-auto">
                <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <tr>
                        <th class="py-3 px-6 text-left">Nama Lengkap</th>
                        <th class="py-3 px-6 text-left">Username</th>
                        <th class="py-3 px-6 text-left">Email</th>
                        <th class="py-3 px-6 text-left">Peran</th>
                        <th class="py-3 px-6 text-left">Dibuat Pada</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="userTableBody" class="text-gray-600 text-sm font-light">
                    </tbody>
                </table>
        </div>
        <div id="paginationContainer" class="mt-6 flex justify-center items-center space-x-1 pagination"></div>
        <p id="paginationInfo" class="text-center text-sm text-gray-600 mt-2"></p>
    </section>
</main>

<div id="addUserModal" class="modal-overlay">
    <div class="modal-content max-w-3xl text-left">
         <h3 class="text-xl font-semibold text-gray-700 mb-4">Tambah Pengguna Baru</h3>
            <form id="addUserForm" action="php_scripts/user_add.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="fullName" class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="fullName" name="full_name" class="form-input" placeholder="e.g., John Doe" required value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="usernameInput" class="form-label">Username <span class="text-red-500">*</span></label>
                            <input type="text" id="usernameInput" name="username" class="form-input" placeholder="e.g., johndoe" required value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="email" class="form-label">Email (Opsional)</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="e.g., john.doe@example.com" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="userRoleSelect" class="form-label">Peran <span class="text-red-500">*</span></label>
                            <select id="userRoleSelect" name="role" class="form-select" required>
                                <option value="" disabled <?php echo !isset($formData['role']) ? 'selected' : ''; ?>>-- Pilih Peran --</option>
                                <option value="staff" <?php echo (isset($formData['role']) && $formData['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                                <option value="supervisor" <?php echo (isset($formData['role']) && $formData['role'] === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                <?php if ($userRole === 'admin'): ?>
                                <option value="admin" <?php echo (isset($formData['role']) && $formData['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                         <label class="form-label font-semibold">Password <span class="text-red-500">*</span></label>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                             <div>
                                <label for="password" class="form-label text-sm">Password Baru</label>
                                <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required>
                            </div>
                            <div>
                                <label for="confirmPassword" class="form-label text-sm">Konfirmasi Password</label>
                                <input type="password" id="confirmPassword" name="confirm_password" class="form-input" placeholder="Ulangi password" required>
                            </div>
                         </div>
                         <div id="passwordMismatchError" class="text-red-500 text-sm mt-2 hidden">Password tidak cocok.</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" id="cancelAddUserBtn" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><span class="material-icons mr-2">person_add</span>Tambah Pengguna</button>
                </div>
            </form>
    </div>
</div>

<div id="deleteUserModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="text-lg font-bold mb-2">Konfirmasi Penghapusan</h3>
        <p class="mb-4">Apakah Anda yakin ingin menghapus pengguna <span id="deleteUserName" class="font-bold text-red-700"></span>? Tindakan ini tidak dapat diurungkan.</p>
        <div class="flex justify-center gap-4">
            <button id="cancelDeleteUserBtn" class="btn btn-secondary">Batal</button>
            <button id="confirmDeleteUserBtn" class="btn bg-red-600 text-white hover:bg-red-700">Hapus</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let debounceTimer;
    const filterForm = document.getElementById('filterForm');
    const searchInput = document.getElementById('userSearchInput');
    const resetBtn = document.getElementById('resetUserSearch');
    const tableBody = document.getElementById('userTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    
    const addUserModal = document.getElementById('addUserModal');
    const openAddUserModalBtn = document.getElementById('openAddUserModalBtn');
    const cancelAddUserBtn = document.getElementById('cancelAddUserBtn');
    const addUserForm = document.getElementById('addUserForm');

    const deleteModal = document.getElementById('deleteUserModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteUserBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteUserBtn');
    let itemToDelete = { id: null, csrf: null };
    
    const currentUserRole = '<?php echo $userRole; ?>';
    
    function fetchUsers(page = 1) {
        const search = searchInput.value;
        history.pushState(null, '', `?page=${page}&search=${encodeURIComponent(search)}`);
        
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Memuat data pengguna...</td></tr>';
        paginationContainer.innerHTML = '';
        paginationInfo.innerHTML = '';

        const url = `php_scripts/get_users_data.php?page=${page}&search=${encodeURIComponent(search)}`;
        
        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject('Network error'))
            .then(result => {
                if (result.success) {
                    renderTable(result.data);
                    renderPagination(result.pagination);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Error: ${result.error || 'Gagal memuat data.'}</td></tr>`;
                }
            })
            .catch(error => {
                 tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">Gagal terhubung ke server.</td></tr>`;
            });
    }

    function renderTable(users) {
        tableBody.innerHTML = '';
        if (users.length === 0) {
            const isFiltering = searchInput.value.trim() !== '';
            let emptyMessage = isFiltering
                ? `Tidak ada pengguna ditemukan untuk pencarian Anda. <a href="user_management.php" class="text-blue-600 hover:underline">Reset Filter?</a>`
                : `Belum ada pengguna lain di sistem.`;

            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-6 px-6 text-gray-600">${emptyMessage}</td></tr>`;
            return;
        }

        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const csrfToken = '<?php echo htmlspecialchars($csrfToken); ?>';

        users.forEach(user => {
            let roleBadgeClass = 'role-staff';
            let rowStatusClass = 'tr-status-default';
            if(user.role === 'admin') { roleBadgeClass = 'role-admin'; rowStatusClass = 'tr-status-admin'; }
            if(user.role === 'supervisor') { roleBadgeClass = 'role-supervisor'; rowStatusClass = 'tr-status-supervisor'; }

            const createdDate = new Date(user.created_at).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'});
            
            let actions = '';
            let canPerformAction = false;
            let disabledTitle = 'Anda tidak dapat mengubah data Anda sendiri.';
            
            if (user.user_id != currentUserId) {
                if (currentUserRole === 'admin') {
                    canPerformAction = true;
                } else if (currentUserRole === 'supervisor' && user.role === 'staff') {
                    canPerformAction = true;
                } else {
                     disabledTitle = 'Anda tidak memiliki izin untuk mengubah pengguna dengan peran ini.';
                }
            }
            
            const dataHref = canPerformAction ? `user_edit.php?user_id=${user.user_id}` : (user.user_id == currentUserId ? 'profile.php' : '#');

            if (canPerformAction) {
                actions = `
                    <a href="${dataHref}" class="btn-icon btn-icon-edit" title="Edit Pengguna"><span class="material-icons">edit</span></a>
                    <button type="button" class="btn-icon btn-icon-delete delete-user-btn" data-id="${user.user_id}" data-name="${escapeHtml(user.username)}" data-csrf="${csrfToken}" title="Hapus Pengguna"><span class="material-icons">delete</span></button>
                `;
            } else {
                actions = `
                    <a href="${dataHref}" class="btn-icon btn-icon-edit ${user.user_id == currentUserId ? '' : 'opacity-50 cursor-not-allowed'}" title="${disabledTitle}"><span class="material-icons">edit</span></a>
                    <button class="btn-icon btn-icon-delete opacity-50 cursor-not-allowed" title="${disabledTitle}" disabled><span class="material-icons">delete</span></button>
                `;
            }

            const row = `
                <tr class="border-b border-gray-200 hover:bg-gray-100 clickable-row table-status-indicator ${rowStatusClass}" data-href="${dataHref}">
                    <td class="py-3 px-6 text-left">${escapeHtml(user.full_name) || '-'}</td>
                    <td class="py-3 px-6 text-left">${escapeHtml(user.username)}</td>
                    <td class="py-3 px-6 text-left">${escapeHtml(user.email) || '-'}</td>
                    <td class="py-3 px-6 text-left"><span class="role-badge ${roleBadgeClass}">${escapeHtml(user.role)}</span></td>
                    <td class="py-3 px-6 text-left">${createdDate}</td>
                    <td class="py-3 px-6 text-center space-x-2 whitespace-nowrap">${actions}</td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }
    
    function renderPagination(pagination) {
        if (!pagination || pagination.totalPages <= 1) {
             paginationContainer.innerHTML = '';
             paginationInfo.innerHTML = `Total ${pagination.totalRecords} pengguna`;
             return;
        }
        
        paginationContainer.innerHTML = '';
        for (let i = 1; i <= pagination.totalPages; i++) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', i);
            const pageLink = document.createElement('a');
            pageLink.href = '?' + urlParams.toString();
            pageLink.textContent = i;
            if (i === pagination.currentPage) {
                pageLink.classList.add('active');
            }
            pageLink.addEventListener('click', (e) => {
                e.preventDefault();
                fetchUsers(i);
            });
            paginationContainer.appendChild(pageLink);
        }

        paginationInfo.innerHTML = `Halaman ${pagination.currentPage} dari ${pagination.totalPages} (Total ${pagination.totalRecords} pengguna)`;
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchUsers(1);
        }, 500);
    });

    resetBtn.addEventListener('click', () => {
        filterForm.reset();
        fetchUsers(1);
    });

    if (addUserForm) {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMismatchError = document.getElementById('passwordMismatchError');

        addUserForm.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                passwordMismatchError.classList.remove('hidden');
                event.preventDefault();
            } else {
                passwordMismatchError.classList.add('hidden');
            }
        });
    }

    function escapeHtml(str) { 
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); 
    }

    tableBody.addEventListener('click', function(e) {
        const row = e.target.closest('tr.clickable-row');
        if (row && !e.target.closest('button, a')) {
            const href = row.dataset.href;
            if (href && href !== '#') {
                window.location.href = href;
            }
        }

        const deleteBtn = e.target.closest('.delete-user-btn');
        if (deleteBtn) {
            e.stopPropagation();
            itemToDelete.id = deleteBtn.dataset.id;
            itemToDelete.csrf = deleteBtn.dataset.csrf;
            document.getElementById('deleteUserName').textContent = `'${deleteBtn.dataset.name}'`;
            deleteModal.classList.add('active');
        }
    });

    confirmDeleteBtn.addEventListener('click', () => {
        if (itemToDelete.id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'php_scripts/user_delete.php'; 
            form.innerHTML = `
                <input type="hidden" name="user_id_to_delete" value="${itemToDelete.id}">
                <input type="hidden" name="csrf_token" value="${itemToDelete.csrf}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });

    cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
    deleteModal.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.classList.remove('active'); });

    openAddUserModalBtn.addEventListener('click', () => addUserModal.classList.add('active'));
    cancelAddUserBtn.addEventListener('click', () => addUserModal.classList.remove('active'));
    addUserModal.addEventListener('click', e => { if (e.target === addUserModal) addUserModal.classList.remove('active'); });
    
    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = parseInt(urlParams.get('page')) || 1;
    fetchUsers(pageFromUrl);
});
</script>
<?php
require_once 'footer.php';
?>