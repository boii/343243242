<?php
/**
 * Reusable Header File (with Elegant "Command Center" Navigation)
 *
 * This version revamps the header navigation into a clean, grouped "Command Center"
 * modal, separating user actions from application navigation for a more intuitive UX.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Partial
 * @package  Sterilabel
 * @author   UI/UX Specialist
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

// --- TANDAI WAKTU MULAI & VERSI APLIKASI ---
define('APP_VERSION', '2.0.0'); // <--- VERSI DIPERBARUI DI SINI
$page_load_start = microtime(true);

if (file_exists('config.php')) {
    require_once 'config.php';
} elseif (file_exists('../config.php')) { 
    require_once '../config.php';
} else {
    die("Kesalahan konfigurasi sistem. Silakan hubungi administrator.");
}

// Session check and user data initialization
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token']; 

$userId = $_SESSION["user_id"] ?? 0;
$username = $_SESSION["username"] ?? 'Pengguna'; 
$userRole = $_SESSION["role"] ?? 'staff';   
$displayName = !empty(trim($_SESSION["full_name"] ?? '')) ? $_SESSION["full_name"] : $username; 

$appInstanceName = $app_settings['app_instance_name'] ?? 'Sterilabel';
$appLogoFilename = $app_settings['app_logo_filename'] ?? '';
$showAppNameBesideLogo = (bool)($app_settings['show_app_name_beside_logo'] ?? true);
$pageTitleFromHeader = ($pageTitle ?? 'Dashboard'); 
$pageTitle = $pageTitleFromHeader . ' - ' . htmlspecialchars($appInstanceName); 

// Fetch notifications
$notifications = [];
$unread_count = 0;
$conn_header = connectToDatabase();
if ($conn_header) {
    $sql_notif = "SELECT notification_id, title, message, link, icon, created_at, is_read 
                  FROM user_notifications 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC LIMIT 10";
    if ($stmt_notif = $conn_header->prepare($sql_notif)) {
        $stmt_notif->bind_param("i", $userId);
        $stmt_notif->execute();
        $result_notif = $stmt_notif->get_result();
        while ($row = $result_notif->fetch_assoc()) {
            $notifications[] = $row;
            if (!$row['is_read']) {
                $unread_count++;
            }
        }
        $stmt_notif->close();
    }
    $conn_header->close();
}

function render_breadcrumbs(string $currentPageTitle = ''): void
{
    // Implementation of breadcrumbs rendering if needed.
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo APP_VERSION; ?>">
</head>
<body class="text-gray-800">

    <div id="page-loading-bar" class="loading-bar"></div>
    <header class="bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl sm:text-3xl font-bold text-blue-600 flex items-center">
                <?php if (!empty($appLogoFilename) && file_exists('uploads/' . $appLogoFilename)): ?>
                    <img src="uploads/<?php echo htmlspecialchars($appLogoFilename) . '?t=' . time(); ?>" alt="Logo <?php echo htmlspecialchars($appInstanceName); ?>" class="header-logo">
                <?php else: ?>
                    <span class="material-icons text-3xl sm:text-4xl">qr_code_scanner</span>
                <?php endif; ?>
                
                <?php if ($showAppNameBesideLogo): ?>
                    <span class="ml-2 hidden sm:inline"><?php echo htmlspecialchars($appInstanceName); ?></span>
                <?php endif; ?>
            </a>
            <div class="flex items-center space-x-1 md:space-x-2">
                
                <button id="search-trigger" class="btn-icon-header" title="Cari">
                    <span class="material-icons">search</span>
                </button>

                <div class="relative dropdown">
                     <button id="notificationBellTrigger" class="btn-icon-header dropdown-trigger relative <?php if ($unread_count > 0) echo 'has-unread'; ?>" title="Notifikasi" aria-haspopup="true">
                        <span class="material-icons">notifications</span>
                        <?php if ($unread_count > 0): ?>
                            <span id="notification-indicator" class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-1 ring-white"></span>
                        <?php endif; ?>
                    </button>
                    <div id="notificationDropdown" class="dropdown-menu notifications-dropdown py-1 right-0"> 
                        <div class="px-4 py-2 border-b font-semibold text-sm">Notifikasi</div>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notif): ?>
                                <a href="<?php echo !empty($notif['link']) ? htmlspecialchars($notif['link']) : '#_'; ?>" class="dropdown-menu-item notification-item block <?php echo $notif['is_read'] ? 'bg-gray-50' : 'bg-blue-50 font-semibold'; ?> hover:bg-gray-100" data-notification-id="<?php echo htmlspecialchars((string)$notif['notification_id']); ?>">
                                    <span class="material-icons text-blue-500"><?php echo htmlspecialchars($notif['icon'] ?? 'campaign'); ?></span>
                                    <div class="notification-content">
                                        <p class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></p>
                                        <p class="notification-message <?php echo $notif['is_read'] ? 'text-gray-500' : 'text-gray-800'; ?>"><?php echo nl2br(htmlspecialchars(substr($notif['message'], 0, 100) . (strlen($notif['message']) > 100 ? '...' : ''))); ?></p>
                                        <p class="notification-time"><?php echo timeAgo($notif['created_at']); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="px-4 py-3 text-center text-sm text-gray-500">Tidak ada notifikasi baru.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="relative dropdown">
                    <button class="btn-icon-header dropdown-trigger" title="Menu Aplikasi" aria-haspopup="true">
                        <span class="material-icons">apps</span>
                    </button>
                    <div class="dropdown-menu command-center-dropdown right-0">
                        <div class="command-center-grid">
                            <?php
                            $staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');
                            $staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');
                            $staffCanViewActivityLog = (isset($app_settings['staff_can_view_activity_log']) && $app_settings['staff_can_view_activity_log'] === '1');
                            ?>
                            <a href="manage_loads.php" class="command-center-item"><span class="material-icons">inventory</span>Manajemen Muatan</a>
                            <a href="label_history.php" class="command-center-item"><span class="material-icons">history</span>Riwayat Label</a>
                            <?php if ($userRole === 'admin' || $userRole === 'supervisor' || $staffCanManageInstruments): ?>
                                <a href="manage_instruments.php" class="command-center-item"><span class="material-icons">build</span>Instrumen</a>
                            <?php endif; ?>
                             <?php if ($userRole === 'admin' || $userRole === 'supervisor' || $staffCanManageSets): ?>
                                <a href="manage_sets.php" class="command-center-item"><span class="material-icons">view_in_ar</span>Set Instrumen</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($userRole === 'admin' || $userRole === 'supervisor'): ?>
                            <div class="command-center-divider">Lanjutan</div>
                            <div class="command-center-grid">
                                <a href="reports.php" class="command-center-item"><span class="material-icons">analytics</span>Laporan</a>
                                <?php if ($userRole === 'admin'): ?>
                                    <a href="manage_master_data.php" class="command-center-item"><span class="material-icons">category</span>Master Data</a>
                                <?php endif; ?>
                                <?php if ($userRole === 'admin' || $userRole === 'supervisor'): ?>
                                    <a href="user_management.php" class="command-center-item"><span class="material-icons">group</span>Pengguna</a>
                                <?php endif; ?>
                                <?php if ($userRole === 'admin' || $userRole === 'supervisor' || $staffCanViewActivityLog): ?>
                                    <a href="activity_log.php" class="command-center-item"><span class="material-icons">plagiarism</span>Log Aktivitas</a>
                                <?php endif; ?>
                                <?php if ($userRole === 'admin'): ?>
                                    <a href="settings.php" class="command-center-item"><span class="material-icons">settings</span>Pengaturan</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="relative dropdown">
                    <button class="dropdown-trigger flex items-center cursor-pointer p-1 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-300" aria-label="Menu pengguna" aria-haspopup="true">
                        <?php $avatarInitialSource = !empty(trim($displayName)) ? $displayName : 'U'; $avatarInitial = strtoupper(substr(htmlspecialchars($avatarInitialSource), 0, 1)); $avatarUrl = "https://placehold.co/40x40/3B82F6/FFFFFF?text=" . urlencode($avatarInitial) . "&font=Nunito"; ?>
                        <img class="w-8 h-8 rounded-full object-cover ring-1 ring-gray-300" src="<?php echo $avatarUrl; ?>" alt="Avatar Pengguna">
                    </button>
                    <div class="dropdown-menu py-1 right-0"> 
                        <div class="px-4 py-2 border-b">
                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($displayName); ?></p>
                            <p class="text-xs text-gray-500"><?php echo ucfirst($userRole); ?></p>
                        </div>
                        <a href="profile.php" class="dropdown-menu-item"><span class="material-icons">account_circle</span>Profil Saya</a>
                        <div class="my-1 border-t border-gray-200"></div>
                        <a href="logout.php" class="dropdown-menu-item text-red-600 hover:bg-red-50"><span class="material-icons">logout</span>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div id="search-modal-overlay">
        <div class="search-modal-container">
            <div class="search-modal-input-wrapper">
                <span class="material-icons">search</span>
                <input type="text" id="global-search-input" class="search-modal-input" placeholder="Cari instrumen, set, label...">
            </div>
            <div id="global-search-results">
                <div class="search-prompt">
                    <span class="material-icons">manage_search</span>
                    <p>Mulai ketik untuk mencari di seluruh sistem.</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $typeClass = is_array($flash['text']) ? 'alert-flash-danger' : 'alert-flash-' . ($flash['type'] ?? 'info');
        echo "<div class='alert-flash " . $typeClass . "'>";
        echo "<span class='material-icons'>info</span><span>";
        
        // --- PERBAIKAN DI SINI ---
        if (is_array($flash['text'])) {
            // Jika array, setiap elemen di-escape (aman)
            echo implode('<br>', array_map('htmlspecialchars', $flash['text']));
        } else {
            // Jika string, cetak langsung tanpa escape (karena sudah disiapkan dengan HTML)
            echo $flash['text'];
        }
        // --- AKHIR PERBAIKAN ---

        echo "</span></div>";
        unset($_SESSION['flash_message']);
    }
    ?>

    <div id="breadcrumb-container" class="py-2"></div>
    
    <div class="main-content-wrapper flex flex-col min-h-[calc(100vh-100px)]"> 
        <div class="flex-grow pb-8"> 
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationBell = document.getElementById('notificationBellTrigger');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationIndicator = document.getElementById('notification-indicator');

        if (notificationBell && notificationDropdown) {
            notificationBell.addEventListener('click', function(e) {
                e.stopPropagation();
                if (notificationIndicator) {
                    fetch('php_scripts/notifications_mark_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ csrf_token: '<?php echo $csrfToken; ?>' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            notificationIndicator.style.display = 'none';
                        }
                    });
                }
            });
        }
        
        const searchTrigger = document.getElementById('search-trigger');
        const searchModalOverlay = document.getElementById('search-modal-overlay');
        const searchInput = document.getElementById('global-search-input');
        const searchResultsContainer = document.getElementById('global-search-results');
        let searchDebounceTimer;

        const openSearch = () => {
            searchModalOverlay.classList.add('active');
            searchInput.focus();
        };

        const closeSearch = () => {
            searchModalOverlay.classList.remove('active');
            searchInput.value = '';
            searchResultsContainer.innerHTML = `<div class="search-prompt"><span class="material-icons">manage_search</span><p>Mulai ketik untuk mencari di seluruh sistem.</p></div>`;
        };

        searchTrigger.addEventListener('click', openSearch);
        searchModalOverlay.addEventListener('click', (e) => {
            if (e.target === searchModalOverlay) {
                closeSearch();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchModalOverlay.classList.contains('active')) {
                closeSearch();
            }
        });

        const handleSearchInput = (query) => {
            clearTimeout(searchDebounceTimer);
            if (query.length < 2) {
                searchResultsContainer.innerHTML = `<div class="search-prompt"><span class="material-icons">manage_search</span><p>Mulai ketik untuk mencari di seluruh sistem.</p></div>`;
                return;
            }
            searchResultsContainer.innerHTML = `<div class="no-results p-6 text-center text-gray-500">Mencari...</div>`;
            
            searchDebounceTimer = setTimeout(() => {
                fetch(`php_scripts/global_search.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        renderSearchResults(data, searchResultsContainer);
                    });
            }, 300);
        };

        const renderSearchResults = (data, container) => {
            container.innerHTML = '';
            let totalResults = 0;
            const categoryMap = { instruments: 'Instrumen', sets: 'Set', loads: 'Muatan', labels: 'Label' };

            for (const category in data) {
                if (data.hasOwnProperty(category) && Array.isArray(data[category]) && data[category].length > 0) {
                    totalResults += data[category].length;
                    const categoryTitle = document.createElement('div');
                    categoryTitle.className = 'result-category';
                    categoryTitle.textContent = categoryMap[category] || category;
                    container.appendChild(categoryTitle);

                    data[category].forEach(item => {
                        const link = document.createElement('a');
                        link.href = item.url;
                        link.className = 'result-item';
                        link.innerHTML = `<div class="mr-4"><span class="material-icons text-gray-400">${item.type === 'set' ? 'inventory_2' : (item.type === 'instrument' ? 'build' : 'label')}</span></div><div><span class="result-item-name">${escapeHtml(item.name)}</span>${item.code ? `<span class="result-item-code">${escapeHtml(item.code)}</span>` : ''}</div>`;
                        container.appendChild(link);
                    });
                }
            }

            if (totalResults === 0) {
                container.innerHTML = '<div class="no-results p-6 text-center text-gray-500">Tidak ada hasil ditemukan untuk "<strong>' + escapeHtml(searchInput.value) + '</strong>".</div>';
            }
        };
        
        searchInput.addEventListener('input', () => handleSearchInput(searchInput.value.trim()));

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
        }
    });
    </script>