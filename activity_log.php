<?php
/**
 * Activity Log Page
 *
 * Displays a comprehensive list of system activities with pagination.
 * This version is revamped for a consistent, elegant UI with automatic filtering.
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

$pageTitle = "Log Aktivitas Sistem";
require_once 'header.php'; // Includes session check, $app_settings, $userRole etc.

// Periksa hak akses, hanya admin dan supervisor (atau staff yang diizinkan) yang boleh masuk
$staffCanViewActivityLog = (isset($app_settings['staff_can_view_activity_log']) && $app_settings['staff_can_view_activity_log'] === '1');
if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanViewActivityLog))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}

// Fetch data untuk filter
$activityErrorMessage = '';
$allUsers = [];

$conn = connectToDatabase();
if ($conn) {
    // Fetch all users for the filter dropdown
    $sqlUsers = "SELECT user_id, username, full_name FROM users ORDER BY username ASC";
    $resultUsers = $conn->query($sqlUsers);
    if ($resultUsers) {
        while ($user = $resultUsers->fetch_assoc()) {
            $allUsers[] = $user;
        }
    } else {
        $activityErrorMessage = "Gagal memuat data pengguna untuk filter.";
    }
    $conn->close();
} else {
    $activityErrorMessage = "Koneksi database gagal.";
}
?>
    <main class="container mx-auto px-6 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-700">Log Aktivitas Sistem</h2>
            <a href="index.php" class="btn btn-secondary">
                <span class="material-icons mr-2">arrow_back</span>Kembali ke Dashboard
            </a>
        </div>

        <?php if (!empty($activityErrorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <span class="material-icons">error</span>
                <span><?php echo htmlspecialchars($activityErrorMessage); ?></span>
            </div>
        <?php endif; ?>

        <section class="card overflow-x-auto">
             <form id="filterForm" method="GET">
                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-700">Riwayat Aktivitas</h3>
                        <p class="text-sm text-gray-600 mt-1">Gunakan filter untuk mencari catatan spesifik. Filter berjalan otomatis.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end p-4 border bg-gray-50 rounded-lg">
                    <div class="lg:col-span-2">
                        <label for="searchQuery" class="form-label text-sm">Cari (Aksi, Detail, IP)</label>
                        <input type="text" id="searchQuery" name="search_query" class="form-input" placeholder="Ketik kata kunci...">
                    </div>
                    <div>
                        <label for="filterUser" class="form-label text-sm">Pengguna</label>
                        <select id="filterUser" name="filter_user" class="form-select">
                            <option value="">Semua Pengguna</option>
                            <?php foreach($allUsers as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label for="dateStart" class="form-label text-sm">Rentang Tanggal</label>
                        <div class="flex flex-col sm:flex-row gap-4">
                           <input type="date" id="dateStart" name="date_start" class="form-input" title="Tanggal Mulai">
                           <input type="date" id="dateEnd" name="date_end" class="form-input" title="Tanggal Akhir">
                           <button type="button" id="resetFilters" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300"><span class="material-icons mr-2">refresh</span>Reset</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mt-4">
                <table class="w-full table-auto">
                    <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <tr>
                            <th class="py-3 px-6 text-left">Timestamp</th>
                            <th class="py-3 px-6 text-left">Pengguna</th>
                            <th class="py-3 px-6 text-left">Tipe Aksi</th>
                            <th class="py-3 px-6 text-left">Detail</th>
                            <th class="py-3 px-6 text-left">Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody" class="text-gray-600 text-sm font-light">
                        </tbody>
                </table>
            </div>
            <div id="paginationContainer" class="mt-4 flex justify-center items-center space-x-1 pagination"></div>
            <p id="paginationInfo" class="text-center text-sm text-gray-600 mt-2"></p>
        </section>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let debounceTimer;
        const filterForm = document.getElementById('filterForm');
        const resetFiltersBtn = document.getElementById('resetFilters');
        const tableBody = document.getElementById('logTableBody');
        const paginationContainer = document.getElementById('paginationContainer');
        const paginationInfo = document.getElementById('paginationInfo');

        function getFilterValues() {
            return new URLSearchParams(new FormData(filterForm)).toString();
        }

        function fetchLogs(page = 1) {
            const filters = getFilterValues();
            history.pushState(null, '', `?page=${page}&${filters}`);
            
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Memuat log aktivitas...</td></tr>';
            paginationContainer.innerHTML = '';
            paginationInfo.innerHTML = '';

            const url = `php_scripts/get_activity_log_data.php?page=${page}&${filters}`;

            fetch(url)
                .then(response => response.ok ? response.json() : Promise.reject('Network error'))
                .then(result => {
                    if (result.success) {
                        renderTable(result.data);
                        renderPagination(result.pagination);
                    } else {
                        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Error: ${result.error || 'Gagal memuat data.'}</td></tr>`;
                    }
                })
                .catch(error => {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Gagal terhubung ke server.</td></tr>`;
                });
        }

        function renderTable(logs) {
            tableBody.innerHTML = '';
            if (logs.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-600">Tidak ada log aktivitas ditemukan untuk filter yang dipilih.</td></tr>';
                return;
            }

            logs.forEach(log => {
                const timestamp = new Date(log.log_timestamp).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'});
                const user = escapeHtml(log.full_name || log.username || 'Sistem');
                const actionType = escapeHtml(log.action_type.replace(/_/g, ' ')).replace(/\b\w/g, l => l.toUpperCase());
                
                const row = `
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left">${timestamp}</td>
                        <td class="py-3 px-6 text-left">${user}</td>
                        <td class="py-3 px-6 text-left">
                            <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">${actionType}</span>
                        </td>
                        <td class="py-3 px-6 text-left whitespace-normal">${escapeHtml(log.details)}</td>
                        <td class="py-3 px-6 text-left font-mono">${escapeHtml(log.ip_address)}</td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }

        function renderPagination(pagination) {
            if (!pagination || pagination.totalPages <= 1) {
                paginationContainer.innerHTML = '';
                paginationInfo.innerHTML = `Total ${pagination.totalRecords} log ditemukan.`;
                return;
            }
            
            paginationContainer.innerHTML = '';
            for (let i = 1; i <= pagination.totalPages; i++) {
                const pageLink = document.createElement('a');
                pageLink.href = '#';
                pageLink.textContent = i;
                if (i === pagination.currentPage) {
                    pageLink.classList.add('active');
                }
                pageLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    fetchLogs(i);
                });
                paginationContainer.appendChild(pageLink);
            }
            paginationInfo.innerHTML = `Halaman ${pagination.currentPage} dari ${pagination.totalPages} (Total ${pagination.totalRecords} log)`;
        }

        function escapeHtml(str) { return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

        const filterInputs = filterForm.querySelectorAll('input, select');
        filterInputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchLogs(1);
                }, 500);
            });
        });
        
        resetFiltersBtn.addEventListener('click', () => {
            filterForm.reset();
            fetchLogs(1);
        });

        fetchLogs(1);
    });
    </script>
<?php
require_once 'footer.php';
?>