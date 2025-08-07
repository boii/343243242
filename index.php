<?php
/**
 * Main Dashboard Page (Streamlined Workflow & Enhanced Navigation)
 *
 * This version implements an elegant two-column layout for the main content area,
 * placing statistics in a sidebar to prioritize charts and activity feeds.
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

$pageTitle = "Dashboard";
require_once 'header.php'; // Includes session, settings, CSRF, notifications, and global helper functions

// --- LOGIKA UNTUK SAPAAN & TANGGAL DINAMIS ---
date_default_timezone_set('Asia/Jakarta');
$hour = (int)date('G');
$greeting = "Selamat Datang";
if ($hour >= 4 && $hour < 11) {
    $greeting = "Selamat Pagi";
} elseif ($hour >= 11 && $hour < 15) {
    $greeting = "Selamat Siang";
} elseif ($hour >= 15 && $hour < 19) {
    $greeting = "Selamat Sore";
} else {
    $greeting = "Selamat Malam";
}
function format_date_indonesian($timestamp) {
    $day_of_week_id = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $month_id = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $day = $day_of_week_id[date('w', $timestamp)];
    $date = date('d', $timestamp);
    $month = $month_id[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp);
    return "$day, $date $month $year";
}
$todayDateFormatted = format_date_indonesian(time());

// --- DATA FETCHING FOR DASHBOARD ---
$recentActivities = [];
$alerts = []; 
$conn = connectToDatabase(); 
if ($conn) {
    // Pastikan status label kedaluwarsa diperbarui sebelum mengambil data
    $sqlUpdateExpired = "UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW()";
    if (!$conn->query($sqlUpdateExpired)) {
        error_log("Error updating expired labels in index.php: " . $conn->error);
    }
    
    // Ambil 7 aktivitas terbaru
    $limitActivities = 7;
    $sqlActivities = "SELECT al.*, u.username as actor_username, u.full_name as actor_full_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.log_timestamp DESC LIMIT ?";
    if ($stmtActivities = $conn->prepare($sqlActivities)) {
        $stmtActivities->bind_param("i", $limitActivities);
        $stmtActivities->execute();
        $resultActivities = $stmtActivities->get_result();
        while ($row = $resultActivities->fetch_assoc()) {
            $recentActivities[] = $row;
        }
        $stmtActivities->close();
    } else {
        error_log("Error preparing statement for recent activities: " . $conn->error);
    }

    // Ambil 5 label yang akan segera kedaluwarsa
    $daysUntilExpiryWarning = (int)($app_settings['default_expiry_days'] ?? 7); 
    $dateThreshold = (new DateTime())->modify("+" . $daysUntilExpiryWarning . " days")->format('Y-m-d H:i:s');
    $currentDate = (new DateTime())->format('Y-m-d H:i:s'); 
    $sqlExpiryAlerts = "SELECT sr.label_unique_id, sr.expiry_date, sr.label_title, CASE sr.item_type WHEN 'instrument' THEN i.instrument_name WHEN 'set' THEN s.set_name ELSE 'Item Tidak Diketahui' END as item_name FROM sterilization_records sr LEFT JOIN instruments i ON sr.item_type = 'instrument' AND sr.item_id = i.instrument_id LEFT JOIN instrument_sets s ON sr.item_type = 'set' AND sr.item_id = s.set_id WHERE sr.status = 'active' AND sr.expiry_date <= ? AND sr.expiry_date >= ? ORDER BY sr.expiry_date ASC LIMIT 5"; 
    if ($stmtAlerts = $conn->prepare($sqlExpiryAlerts)) {
        $stmtAlerts->bind_param("ss", $dateThreshold, $currentDate); 
        $stmtAlerts->execute(); 
        $resultAlerts = $stmtAlerts->get_result();
        $nowDateTime = new DateTime(); 
        while ($rowAlert = $resultAlerts->fetch_assoc()) {
            $expiryDateTime = new DateTime($rowAlert['expiry_date']); 
            $interval = $nowDateTime->diff($expiryDateTime); 
            $daysRemaining = (int)$interval->format('%r%a'); 
            
            $urgency = 'medium'; 
            $urgencyText = "Akan kedaluwarsa dalam " . $daysRemaining . " hari.";
            if ($daysRemaining <= 1 && $daysRemaining >= 0) { $urgency = 'critical'; $urgencyText = $daysRemaining == 0 ? "Kedaluwarsa HARI INI!" : "Kedaluwarsa BESOK!"; } 
            elseif ($daysRemaining <= 3 && $daysRemaining > 1) { $urgency = 'high'; }
            
            $alertItemName = !empty(trim($rowAlert['label_title'] ?? '')) ? $rowAlert['label_title'] : ($rowAlert['item_name'] ?? 'N/A');
            $alerts[] = ['urgency' => $urgency, 'message' => htmlspecialchars($alertItemName) . " (ID: " . htmlspecialchars($rowAlert['label_unique_id']) . ")", 'details' => $urgencyText, 'link' => 'verify_label.php?uid=' . urlencode($rowAlert['label_unique_id'])];
        } 
        $stmtAlerts->close();
    } else { 
        error_log("Error preparing statement for expiry alerts: " . $conn->error); 
    }
    $conn->close();
}

$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .info-center .tab-nav { display: flex; border-bottom: 1px solid #e5e7eb; }
        .info-center .tab-link { padding: 0.75rem 1rem; font-weight: 600; color: #6b7280; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -1px; transition: color 0.2s, border-color 0.2s; }
        .info-center .tab-link:hover { color: #3b82f6; }
        .info-center .tab-link.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .info-center .tab-content { display: none; padding-top: 1rem; }
        .info-center .tab-content.active { display: block; }
        /* PERUBAHAN: Menambahkan gaya untuk grid dashboard dan daftar */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr; /* Default 1 kolom untuk mobile */
            gap: 2rem;
        }
        @media (min-width: 1024px) { /* Terapkan grid 3 kolom untuk layar besar (lg) */
            .dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        .dashboard-main-content {
            grid-column: span 1 / span 1;
        }
        .dashboard-sidebar {
            grid-column: span 1 / span 1;
        }
        @media (min-width: 1024px) {
            .dashboard-main-content {
                grid-column: span 2 / span 2;
            }
            .dashboard-sidebar {
                grid-column: span 1 / span 1;
            }
        }
        .alert-list-item, .activity-list-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0.25rem;
        }

    </style>
    <main class="container mx-auto px-6 py-8">

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-8 gap-y-4 text-center sm:text-left">
            <div>
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-800">
                    <?php echo $greeting; ?>, <?php echo htmlspecialchars(explode(' ', $displayName)[0]); ?>!
                </h2>
                <p id="dashboard-subtitle" class="text-gray-600 mt-1">Berikut adalah ringkasan dan statistik sistem.</p>
            </div>
            <div class="text-gray-500 font-medium">
                <p class="text-sm"><?php echo $todayDateFormatted; ?></p>
                <p id="live-clock" class="text-lg font-semibold text-gray-700"></p>
            </div>
        </div>

        <section id="main-navigation" class="mb-8">
            <h3 class="text-sm font-semibold text-gray-500 mb-2 uppercase tracking-wider">Navigasi Utama</h3>
            <div class="navigation-bar">
                <?php if ($userRole === 'admin' || $userRole === 'supervisor'): ?>
                    <a href="manage_loads.php" class="nav-action-button nav-primary nav-color-loads"><span class="material-icons">inventory</span><span>Manajemen Muatan</span></a>
                    <a href="cycle_validation.php" class="nav-action-button nav-color-cycles"><span class="material-icons">cyclone</span><span>Riwayat Siklus</span></a>
                    <a href="label_history.php" class="nav-action-button nav-color-history"><span class="material-icons">history</span><span>Riwayat Label</span></a>
                    <a href="manage_instruments.php" class="nav-action-button nav-color-instruments"><span class="material-icons">build</span><span>Instrumen</span></a>
                    <a href="manage_sets.php" class="nav-action-button nav-color-sets"><span class="material-icons">view_in_ar</span><span>Set Instrumen</span></a>
                <?php else: // Tampilan untuk Staff ?>
                    <a href="manage_loads.php" class="nav-action-button nav-primary nav-color-loads"><span class="material-icons">inventory</span><span>Manajemen Muatan</span></a>
                    <a href="label_history.php" class="nav-action-button nav-color-history"><span class="material-icons">history</span><span>Riwayat Label</span></a>
                    <?php if ($staffCanManageInstruments): ?><a href="manage_instruments.php" class="nav-action-button nav-color-instruments"><span class="material-icons">build</span><span>Instrumen</span></a><?php endif; ?>
                    <?php if ($staffCanManageSets): ?><a href="manage_sets.php" class="nav-action-button nav-color-sets"><span class="material-icons">view_in_ar</span><span>Set Instrumen</span></a><?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <div class="dashboard-grid">

            <div class="dashboard-main-content space-y-8">
                <section id="stats-charts">
                    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
                        <div class="xl:col-span-3 card"><h3 class="text-lg font-semibold text-gray-700 mb-2">Aktivitas Label (7 Hari Terakhir)</h3><div class="chart-container"><div id="weeklyLabelsChartLoader" class="chart-loader">Memuat data...</div><canvas id="weeklyLabelsChart" style="display: none;"></canvas></div></div>
                        <div class="xl:col-span-2 card"><h3 class="text-lg font-semibold text-gray-700 mb-2">Status Inventaris Label</h3><div class="chart-container"><div id="labelStatusChartLoader" class="chart-loader">Memuat data...</div><canvas id="labelStatusChart" style="display: none;"></canvas></div></div>
                    </div>
                </section>
                
                <section class="info-center card">
                    <div class="tab-nav">
                        <button class="tab-link active" data-target="tab-alerts">Peringatan</button>
                        <button class="tab-link" data-target="tab-activity">Aktivitas</button>
                    </div>

                    <div id="tab-alerts" class="tab-content active">
                        <?php if (!empty($alerts)): ?>
                            <ul class="space-y-1">
                                <?php foreach ($alerts as $alert): ?>
                                    <?php 
                                        $alertIcon = 'notification_important';
                                        $alertIconColor = 'text-yellow-600';
                                        if ($alert['urgency'] === 'critical') { $alertIcon = 'error'; $alertIconColor = 'text-red-600'; }
                                        elseif ($alert['urgency'] === 'high') { $alertIcon = 'warning'; $alertIconColor = 'text-orange-600'; }
                                    ?>
                                    <li class="alert-list-item">
                                        <span class="material-icons <?php echo $alertIconColor; ?> mr-3"><?php echo $alertIcon; ?></span>
                                        <div class="flex-grow">
                                            <a href="<?php echo htmlspecialchars($alert['link'] ?? '#'); ?>" class="text-sm text-gray-700 font-medium hover:underline" title="Lihat Detail Label"><?php echo $alert['message']; ?></a>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($alert['details'] ?? ''); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm py-4 text-center">Tidak ada peringatan saat ini.</p>
                        <?php endif; ?>
                    </div>
                    <div id="tab-activity" class="tab-content">
                        <?php if (!empty($recentActivities)): ?>
                            <ul>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php 
                                        $formattedActivity = formatActivityMessage($activity); 
                                        $actor = htmlspecialchars($activity['actor_full_name'] ?: ($activity['actor_username'] ?: 'Sistem'));
                                        $time = timeAgo($activity['log_timestamp']);
                                        $fullTimestamp = isset($activity['log_timestamp']) ? (new DateTime($activity['log_timestamp']))->format('d M Y, H:i:s') : 'Tidak diketahui';
                                    ?>
                                    <li class="py-3 border-b border-gray-200 flex items-center justify-between activity-list-item">
                                        <div class="flex-grow pr-3">
                                            <p class="text-gray-800 text-sm leading-tight"><?php echo $formattedActivity['message']; ?></p>
                                            <p class="text-xs text-gray-500 mt-1" title="<?php echo $fullTimestamp; ?>">Oleh <?php echo $actor; ?> - <?php echo $time; ?></p>
                                        </div>
                                        <span class="material-icons <?php echo $formattedActivity['iconColor']; ?>"><?php echo $formattedActivity['icon']; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="mt-4 text-center">
                                <a href="activity_log.php" class="text-sm font-semibold text-blue-600 hover:underline">Lihat Semua Aktivitas &rarr;</a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm py-4 text-center">Belum ada aktivitas terbaru.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="dashboard-sidebar">
                <section id="stat-cards" class="space-y-6">
                    <a href="label_history.php?status=active" class="stat-card clickable-card no-underline" title="Lihat semua label aktif">
                        <div class="stat-header">
                            <div class="icon-container bg-green-100"><span class="material-icons text-green-600">check_circle</span></div>
                            <div>
                                <p class="stat-title">Label Aktif</p>
                                <p id="stats-active-labels" class="stat-number"><span class="stat-loading"></span></p>
                            </div>
                        </div>
                        <div id="trend-active-labels" class="stat-trend trend-neutral"></div>
                    </a>
                    <a href="manage_instruments.php?status=tersedia" class="stat-card clickable-card no-underline" title="Lihat semua instrumen yang tersedia">
                         <div class="stat-header">
                            <div class="icon-container bg-blue-100"><span class="material-icons text-blue-600">build</span></div>
                            <div>
                                <p class="stat-title">Instrumen Tersedia</p>
                                <p id="stats-available-instruments" class="stat-number"><span class="stat-loading"></span></p>
                            </div>
                        </div>
                        <div id="trend-available-instruments" class="stat-trend trend-neutral"></div>
                    </a>
                    <a href="label_history.php?status=expired" class="stat-card clickable-card no-underline" title="Lihat semua label yang sudah kedaluwarsa">
                        <div class="stat-header">
                            <div class="icon-container bg-red-100"><span class="material-icons text-red-600">event_busy</span></div>
                            <div>
                                <p class="stat-title">Label Kedaluwarsa</p>
                                <p id="stats-expired-labels" class="stat-number"><span class="stat-loading"></span></p>
                            </div>
                        </div>
                        <div id="trend-expired-labels" class="stat-trend trend-neutral"></div>
                    </a>
                    <a href="cycle_validation.php" class="stat-card clickable-card no-underline" title="Lihat semua riwayat siklus">
                        <div class="stat-header">
                            <div class="icon-container bg-purple-100"><span class="material-icons text-purple-600">history_toggle_off</span></div>
                            <div>
                                <p class="stat-title">Total Siklus Hari Ini</p>
                                <p id="stats-cycles-today" class="stat-number"><span class="stat-loading"></span></p>
                            </div>
                        </div>
                    </a>
                </section>
            </div> 

        </div>
    </main>
    
    <div class="fab-container"><div id="fab-help" class="fab animate-attention-glow" title="Buka Panduan"><span class="material-icons">help_outline</span></div></div>
    <div id="guideModal" class="modal-overlay"><div class="modal-content"><div class="guide-section text-center"><h3>Selamat Datang di <?php echo htmlspecialchars($app_settings['app_instance_name'] ?? 'Sterilabel'); ?>!</h3><p>Panduan ini akan membantu Anda memahami alur kerja utama aplikasi dalam 3 langkah mudah.</p></div><div class="guide-section"><h4>Memahami 3 Istilah Kunci</h4><ul><li><strong><span class="text-blue-600">Muatan (Load)</span>:</strong> Anggap ini sebagai "Keranjang". Di sinilah Anda mengumpulkan semua alat (instrumen & set) yang akan disterilkan bersama-sama.</li><li><strong><span class="text-orange-600">Siklus (Cycle)</span>:</strong> Ini adalah "Proses"-nya. Satu siklus berarti satu kali proses sterilisasi di dalam mesin dari awal hingga akhir.</li><li><strong><span class="text-green-600">Label (Label)</span>:</strong> Ini adalah "Sertifikat Lulus" untuk setiap alat. QR Code pada label adalah bukti bahwa alat tersebut telah lulus proses sterilisasi dan aman digunakan.</li></ul></div><div class="guide-section"><h4>Alur Kerja Utama (3 Langkah)</h4><ol><li><strong>Siapkan Muatan:</strong> Buka <a href="manage_loads.php" class="text-blue-600 hover:underline">Manajemen Muatan</a>, klik "Buat Muatan Baru", lalu masukkan semua alat yang ingin disterilkan ke dalamnya.</li><li><strong>Jalankan Siklus:</strong> Setelah semua alat masuk, klik tombol "Jalankan Siklus". Aplikasi akan secara otomatis menjalankan proses dan mencatat hasilnya.</li><li><strong>Cetak Label:</strong> Jika siklus berhasil, tombol "Manajemen Cetak" akan muncul. Klik tombol tersebut, dan semua label untuk alat di muatan itu siap ditempel. Selesai!</li></ol></div><div class="mt-6 text-right"><button type="button" id="closeGuideModal" class="btn btn-secondary">Mengerti, Tutup Panduan</button></div></div></div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // PERUBAHAN: Menambahkan event listener untuk tab
        const fabHelp = document.getElementById('fab-help');
        const guideModal = document.getElementById('guideModal');
        const closeGuideModalBtn = document.getElementById('closeGuideModal');

        if (fabHelp && guideModal) {
            fabHelp.addEventListener('click', () => guideModal.classList.add('active'));
            closeGuideModalBtn.addEventListener('click', () => guideModal.classList.remove('active'));
            guideModal.addEventListener('click', (e) => { if (e.target === guideModal) guideModal.classList.remove('active'); });
        }
        
        const clockElement = document.getElementById('live-clock');
        function updateClock() { if (clockElement) { clockElement.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }); } }
        setInterval(updateClock, 1000);
        updateClock();

        const tabLinks = document.querySelectorAll('.info-center .tab-link');
        const tabContents = document.querySelectorAll('.info-center .tab-content');
        tabLinks.forEach(link => { link.addEventListener('click', () => { const targetId = link.dataset.target; tabLinks.forEach(l => l.classList.remove('active')); tabContents.forEach(c => c.classList.remove('active')); link.classList.add('active'); document.getElementById(targetId).classList.add('active'); }); });
        
        function displayTrend(elementId, trendValue) {
            const trendElement = document.getElementById(elementId);
            if (!trendElement) return;
            trendValue = parseInt(trendValue, 10);
            let icon = 'horizontal_rule', colorClass = 'trend-neutral', text = 'vs kemarin';
            if (trendValue > 0) { icon = 'arrow_upward'; colorClass = 'trend-up'; text = `+${trendValue} ${text}`; } 
            else if (trendValue < 0) { icon = 'arrow_downward'; colorClass = 'trend-down'; text = `${trendValue} ${text}`; } 
            else { text = `Sama seperti kemarin`; }
            trendElement.innerHTML = `<span class="material-icons">${icon}</span> ${text}`;
            trendElement.className = `stat-trend ${colorClass}`;
        }

        fetch('php_scripts/get_dashboard_stats.php')
            .then(response => response.ok ? response.json() : Promise.reject('Network error'))
            .then(data => {
                if(data.error) { throw new Error(data.error); }

                document.getElementById('stats-active-labels').textContent = data.active_labels || '0';
                document.getElementById('stats-available-instruments').textContent = data.available_instruments || '0';
                document.getElementById('stats-expired-labels').textContent = data.expired_labels || '0';
                document.getElementById('stats-cycles-today').textContent = data.cycles_today || '0';
                
                if (data.trends) {
                    displayTrend('trend-active-labels', data.trends.active_labels);
                    displayTrend('trend-available-instruments', data.trends.available_instruments);
                    displayTrend('trend-expired-labels', data.trends.expired_labels);
                }
                
                renderLabelStatusChart(data.label_status_counts);
                renderWeeklyLabelsChart(data.weekly_label_creation);
            })
            .catch(error => {
                console.error('Error fetching dashboard stats:', error);
                document.querySelectorAll('.stat-loading').forEach(loader => {
                    const parent = loader.parentElement;
                    if(parent) parent.textContent = 'N/A';
                });
            });

        function renderLabelStatusChart(statusData) {
            const ctx = document.getElementById('labelStatusChart');
            const loader = document.getElementById('labelStatusChartLoader');
            if (!ctx || !loader) return;
            const labels = ['Aktif', 'Digunakan', 'Kedaluwarsa'];
            const data = [ statusData.active, statusData.used, statusData.expired ];
            loader.style.display = 'none';
            ctx.style.display = 'block';
            new Chart(ctx, { type: 'doughnut', data: { labels: labels, datasets: [{ label: 'Jumlah Label', data: data, backgroundColor: ['rgba(22, 163, 74, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(220, 38, 38, 0.7)'], borderColor: ['#ffffff'], borderWidth: 2 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 15, boxWidth: 12, font: { size: 11 } } } } } });
        }

        function renderWeeklyLabelsChart(weeklyData) {
            const ctx = document.getElementById('weeklyLabelsChart');
            const loader = document.getElementById('weeklyLabelsChartLoader');
            if (!ctx || !loader) return;
            const labels = weeklyData.map(d => d.day);
            const data = weeklyData.map(d => d.count);
            loader.style.display = 'none';
            ctx.style.display = 'block';
            new Chart(ctx, { type: 'bar', data: { labels: labels, datasets: [{ label: 'Label Dibuat', data: data, backgroundColor: 'rgba(37, 99, 235, 0.6)', borderColor: 'rgba(37, 99, 235, 1)', borderWidth: 1, borderRadius: 4 }] }, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { title: () => '', label: context => ` ${context.raw} Label` } } } } });
        }
    });
    </script>
<?php
require_once 'footer.php'; 
?>