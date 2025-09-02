<?php
/**
 * Database Configuration and Application Settings Loader
 *
 * Contains the settings for connecting to the MariaDB database,
 * and loads all application settings into a global array.
 * This version now includes universal helper functions.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Configuration
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

// --- Pengaturan Pelaporan Error (HAPUS ATAU KOMENTARI DI PRODUKSI) ---
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
// --- Akhir Pengaturan Pelaporan Error ---


// Atur zona waktu default aplikasi Anda
date_default_timezone_set('Asia/Jakarta');

// Database credentials - Ganti dengan kredensial database Anda
define('DB_SERVER', 'localhost'); 
define('DB_USERNAME', 'edfa6624_steril'); 
define('DB_PASSWORD', 's6Bv8A0FQt0GET4s'); 
define('DB_NAME', 'edfa6624_steril'); 

/**
 * Attempt to connect to MariaDB database.
 *
 * This function establishes a connection to the database using mysqli.
 * It also sets the character set to utf8mb4 and connection timezone to WIB (+07:00).
 *
 * @return mysqli|false A mysqli object on success, or false on failure.
 */
function connectToDatabase(): mysqli|false
{
    $connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($connection->connect_error) {
        error_log("Database Connection failed: " . $connection->connect_error);
        return false; 
    }

    // Atur zona waktu untuk koneksi ini ke Waktu Indonesia Barat (GMT+7)
    $connection->query("SET time_zone = '+07:00'");

    if (!$connection->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $connection->error);
    }
    return $connection;
}

// --- Load Application Settings ---
global $app_settings; 
$app_settings_from_db = []; 

$default_settings = [
    'app_instance_name'              => 'Sterilabel', 
    'default_expiry_days'            => '30',         
    'enable_pending_validation'      => '0', 
    'show_status_block_on_detail_page' => '1',
    'app_logo_filename'              => '', 
    'show_app_name_beside_logo'      => '1',
    'print_template'                 => 'thermal',    
    'thermal_fields_config'          => '{}', 
    'thermal_qr_position'            => 'bottom_center',
    'thermal_qr_size'                => 'medium',
    'staff_can_manage_instruments'   => '0', 
    'staff_can_manage_sets'          => '0',
    'staff_can_validate_cycles'      => '0',
    'staff_can_view_activity_log'    => '0',
    'public_usage_pin'               => '', // PIN untuk menandai digunakan dari halaman publik
    'thermal_custom_text_1'          => '', 
    'thermal_custom_text_2'          => '',
    'thermal_paper_width_mm'         => '70',
    'thermal_paper_height_mm'        => '40' 
];

$conn_settings = connectToDatabase(); 

if ($conn_settings) {
    $sql = "SELECT setting_name, setting_value FROM app_settings";
    $result = $conn_settings->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (array_key_exists($row['setting_name'], $default_settings)) {
                $app_settings_from_db[$row['setting_name']] = $row['setting_value'];
            }
        }
        $result->free();
    } else {
        error_log("Failed to load application settings from DB: " . $conn_settings->error);
    }
    $conn_settings->close();
} else {
    error_log("Database connection failed, cannot load application settings.");
}

// Merge defaults with DB settings. DB values take precedence.
$app_settings = array_merge($default_settings, $app_settings_from_db);

// Ensure thermal_fields_config is an array and structured correctly
// --- PERBAIKAN: Memperbarui struktur default agar mencakup semua field ---
$completeDefaultFieldStructure = [ 
    'item_name'         => ['visible' => true,  'order' => 1, 'label' => 'Nama Item', 'hide_label' => false, 'custom_label' => ''],
    'label_title'       => ['visible' => true,  'order' => 2, 'label' => 'Judul Label', 'hide_label' => false, 'custom_label' => ''],
    'label_unique_id'   => ['visible' => false, 'order' => 3, 'label' => 'ID Label Unik', 'hide_label' => false, 'custom_label' => ''],
    'created_at'        => ['visible' => true,  'order' => 4, 'label' => 'Tanggal Buat', 'hide_label' => false, 'custom_label' => ''],
    'expiry_date'       => ['visible' => true,  'order' => 5, 'label' => 'Tanggal Kedaluwarsa', 'hide_label' => false, 'custom_label' => ''],
    'load_name'         => ['visible' => false, 'order' => 6, 'label' => 'Nama Muatan', 'hide_label' => false, 'custom_label' => ''],
    'cycle_number'      => ['visible' => false, 'order' => 7, 'label' => 'Nomor Siklus', 'hide_label' => false, 'custom_label' => ''],
    'machine_name'      => ['visible' => false, 'order' => 8, 'label' => 'Nama Mesin', 'hide_label' => false, 'custom_label' => ''],
    'cycle_operator_name' => ['visible' => false, 'order' => 9, 'label' => 'Operator Siklus', 'hide_label' => false, 'custom_label' => ''],
    'cycle_date'        => ['visible' => false, 'order' => 10, 'label' => 'Tanggal Siklus', 'hide_label' => false, 'custom_label' => ''],
    'load_creator_name'   => ['visible' => false, 'order' => 11, 'label' => 'Pembuat Muatan', 'hide_label' => false, 'custom_label' => ''],
    'destination_department_name' => ['visible' => false, 'order' => 12, 'label' => 'Departemen Tujuan', 'hide_label' => false, 'custom_label' => ''],
    'creator_username'  => ['visible' => false, 'order' => 13, 'label' => 'Dibuat Oleh (Label)', 'hide_label' => false, 'custom_label' => ''],
    'used_at'           => ['visible' => false, 'order' => 14, 'label' => 'Tanggal Digunakan', 'hide_label' => false, 'custom_label' => ''],
    'notes'             => ['visible' => false, 'order' => 15, 'label' => 'Catatan Tambahan', 'hide_label' => false, 'custom_label' => ''],
    'custom_text_1'     => ['visible' => false, 'order' => 16, 'label' => 'Teks Kustom 1', 'hide_label' => true,  'custom_label' => ''],
    'custom_text_2'     => ['visible' => false, 'order' => 17, 'label' => 'Teks Kustom 2', 'hide_label' => true,  'custom_label' => '']
];

$decodedFieldsConfig = null;
if (isset($app_settings['thermal_fields_config']) && is_string($app_settings['thermal_fields_config'])) {
    $decodedFieldsConfig = json_decode($app_settings['thermal_fields_config'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Failed to decode thermal_fields_config JSON from database: " . json_last_error_msg() . ". JSON string was: " . $app_settings['thermal_fields_config']);
        $decodedFieldsConfig = []; 
    }
} elseif (isset($app_settings['thermal_fields_config']) && is_array($app_settings['thermal_fields_config'])) {
    $decodedFieldsConfig = $app_settings['thermal_fields_config'];
} else {
    $decodedFieldsConfig = []; 
}

$finalThermalFieldsConfig = [];
foreach ($completeDefaultFieldStructure as $key => $defaultValues) {
    $dbValue = $decodedFieldsConfig[$key] ?? []; 
    $finalThermalFieldsConfig[$key] = [
        'visible'      => (bool)($dbValue['visible'] ?? $defaultValues['visible']),
        'order'        => (int)($dbValue['order'] ?? $defaultValues['order']),
        'label'        => $defaultValues['label'], 
        'hide_label'   => (bool)($dbValue['hide_label'] ?? $defaultValues['hide_label']),
        'custom_label' => (string)($dbValue['custom_label'] ?? $defaultValues['custom_label'])
    ];
}
$app_settings['thermal_fields_config'] = $finalThermalFieldsConfig;


// Start the session if it hasn't been started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Logs an activity into the database.
 *
 * @param string      $actionType         A code for the action, e.g., 'CREATE_USER'.
 * @param int|null    $userId             The ID of the user performing the action. Can be null for system actions.
 * @param string      $details            A descriptive text of the action.
 * @param string|null $targetType         Optional: The type of the target entity, e.g., 'instrument'.
 * @param int|null    $targetId           Optional: The ID of the target entity.
 * @return void
 */
function log_activity(string $actionType, ?int $userId, string $details, ?string $targetType = null, ?int $targetId = null): void
{
    $conn = connectToDatabase();
    if (!$conn) {
        error_log("LOGGING FAILED: Could not connect to database.");
        return;
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $sql = "INSERT INTO activity_log (user_id, action_type, details, target_type, target_id, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssis", $userId, $actionType, $details, $targetType, $targetId, $ip_address);
        if (!$stmt->execute()) {
            error_log("LOGGING FAILED: Could not execute statement for action " . $actionType . ". Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("LOGGING FAILED: Could not prepare statement. Error: " . $conn->error);
    }
    $conn->close();
}


// --- GLOBAL HELPER FUNCTIONS ---

if (!function_exists('getUniversalStatusBadge')) {
    /**
     * Generates consistent text and CSS classes for status badges across the application.
     *
     * @param string $status The raw status from the database.
     * @return array An associative array with 'text' and 'class' keys.
     */
    function getUniversalStatusBadge(string $status): array
    {
        $statusLower = strtolower(str_replace(' ', '_', $status));
        $defaultClass = 'bg-gray-100 text-gray-700';
        $defaultText = ucfirst(str_replace('_', ' ', $status));

        $statusMap = [
            // Label & General Statuses
            'active' => ['text' => 'Aktif', 'class' => 'bg-green-100 text-green-800'],
            'used' => ['text' => 'Telah Digunakan', 'class' => 'bg-blue-100 text-blue-800'],
            'expired' => ['text' => 'Kedaluwarsa', 'class' => 'bg-red-100 text-red-800'],
            'pending_validation' => ['text' => 'Pending Validasi', 'class' => 'bg-yellow-100 text-yellow-800'],
            'recalled' => ['text' => 'Ditarik Kembali', 'class' => 'bg-purple-100 text-purple-700'],
            'voided' => ['text' => 'Dibatalkan', 'class' => 'bg-gray-400 text-white'],

            // Load & Cycle Statuses
            'persiapan' => ['text' => 'Persiapan', 'class' => 'bg-gray-200 text-gray-800'],
            'menunggu_validasi' => ['text' => 'Menunggu Validasi', 'class' => 'bg-yellow-100 text-yellow-800'],
            'completed' => ['text' => 'Selesai (Lulus)', 'class' => 'bg-green-100 text-green-800'],
            'selesai' => ['text' => 'Selesai (Lulus)', 'class' => 'bg-green-100 text-green-800'],
            'failed' => ['text' => 'Gagal', 'class' => 'bg-red-100 text-red-800'],
            'gagal' => ['text' => 'Gagal', 'class' => 'bg-red-100 text-red-800'],
            
            // Instrument Statuses
            'tersedia' => ['text' => 'Tersedia', 'class' => 'bg-green-100 text-green-800'],
            'sterilisasi' => ['text' => 'Sterilisasi', 'class' => 'bg-blue-100 text-blue-800'],
            'perbaikan' => ['text' => 'Perbaikan', 'class' => 'bg-yellow-100 text-yellow-800'],
            'rusak' => ['text' => 'Rusak', 'class' => 'bg-red-100 text-red-800'],
        ];

        return $statusMap[$statusLower] ?? ['text' => $defaultText, 'class' => $defaultClass];
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Converts a timestamp into a human-readable "time ago" string.
     * e.g., "5 menit lalu", "2 jam lalu", "3 hari lalu".
     *
     * @param string $datetime The timestamp string (e.g., from the database).
     * @return string The formatted "time ago" string.
     */
    function timeAgo(string $datetime): string {
        try {
            $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
            $ago = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
            $diff = $now->diff($ago);

            if ($diff->y == 0 && $diff->m == 0 && $diff->d == 0 && $diff->h == 0 && $diff->i < 1) {
                return 'baru saja';
            }

            $diff->w = floor($diff->d / 7);
            $diff->d -= $diff->w * 7;
            $string = ['y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu', 'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik'];
            foreach ($string as $k => &$v) {
                if ($diff->$k) $v = $diff->$k . ' ' . $v; else unset($string[$k]);
            }
            return implode(', ', array_slice($string, 0, 1)) . ' lalu';
        } catch (Exception $e) {
            error_log("Error in timeAgo function (config.php): " . $e->getMessage() . " for datetime: " . $datetime);
            return 'tanggal tidak valid';
        }
    }
}

if (!function_exists('formatActivityMessage')) {
    /**
     * Formats an activity log entry for display with appropriate icons and links.
     *
     * @param array $activity The activity log row from the database.
     * @return array An associative array with 'message', 'icon', and 'iconColor'.
     */
    function formatActivityMessage(array $activity): array
    {
        $message = htmlspecialchars($activity['details']); // Default message is the raw detail
        $icon = 'info';
        $iconColor = 'text-gray-500';

        $actionType = $activity['action_type'] ?? 'UNKNOWN';
        $targetType = $activity['target_type'] ?? null;
        $targetId = $activity['target_id'] ?? null;
        $details = $activity['details'] ?? '';

        switch ($actionType) {
            case 'CREATE_LABEL':
                $icon = 'style';
                $iconColor = 'text-green-500';
                if (preg_match("/UID: ([A-Z0-9]+)/", $details, $matches)) {
                    $uid = $matches[1];
                    $message = preg_replace(
                        "/'([^']*)'/",
                        "'<a href=\"verify_label.php?uid=" . urlencode($uid) . "\" class=\"text-blue-600 hover:underline\">$1</a>'",
                        htmlspecialchars($details)
                    );
                }
                break;
            
            case 'CREATE_LOAD': case 'PROCESS_LOAD': case 'VALIDATE_CYCLE': case 'GENERATE_LABELS': case 'RECALL_LOAD':
                $icon = 'inventory'; $iconColor = 'text-blue-700'; break;
            case 'DELETE_LOAD':
                $icon = 'delete_forever'; $iconColor = 'text-red-500'; break;
            case 'CREATE_CYCLE': case 'UPDATE_CYCLE': 
                $icon = 'cyclone'; $iconColor = 'text-orange-500'; break;
            case 'DELETE_CYCLE': 
                $icon = 'delete'; $iconColor = 'text-red-500'; break;
            case 'CREATE_USER': 
                $icon = 'person_add'; $iconColor = 'text-purple-500'; break;
            case 'UPDATE_USER': 
                $icon = 'manage_accounts'; $iconColor = 'text-purple-500'; break;
            case 'DELETE_USER': 
                $icon = 'person_remove'; $iconColor = 'text-red-500'; break;
            case 'CREATE_INSTRUMENT': 
                $icon = 'add'; $iconColor = 'text-indigo-500'; break;
            case 'UPDATE_INSTRUMENT': case 'UPDATE_INSTRUMENT_STATUS':
                 $icon = 'edit'; $iconColor = 'text-indigo-500'; break;
            case 'DELETE_INSTRUMENT': 
                $icon = 'delete'; $iconColor = 'text-red-500'; break;
            case 'CREATE_SET': 
                $icon = 'inventory_2'; $iconColor = 'text-teal-500'; break;
            case 'UPDATE_SET': 
                $icon = 'edit_note'; $iconColor = 'text-teal-500'; break;
            case 'DELETE_SET': 
                $icon = 'delete_sweep'; $iconColor = 'text-red-500'; break;
            case 'VALIDATE_LABEL': 
                $icon = 'task_alt'; $iconColor = 'text-green-600'; break;
            case 'MARK_LABEL_USED': 
                $icon = 'check_circle_outline'; $iconColor = 'text-blue-500'; break;
            case 'RECALL_LABEL': 
                $icon = 'report_problem'; $iconColor = 'text-orange-500'; break;
        }

        if ($targetId && substr($actionType, 0, 6) !== 'DELETE') {
            $link = null;
            if ($targetType === 'label' && preg_match("/UID: ([A-Z0-9]+)/", $details, $matches)) {
                $link = "verify_label.php?uid=" . urlencode($matches[1]);
            }
            if ($targetType === 'instrument') $link = "instrument_detail.php?instrument_id={$targetId}";
            if ($targetType === 'set') $link = "set_detail.php?set_id={$targetId}";
            if ($targetType === 'cycle') $link = "cycle_detail.php?cycle_id={$targetId}";
            if ($targetType === 'load') $link = "load_detail.php?load_id={$targetId}";
            if ($targetType === 'user') $link = "user_edit.php?user_id={$targetId}";
            
            if ($link) {
                $message = "<a href='{$link}' class='text-blue-600 hover:underline'>{$message}</a>";
            }
        }
        
        return ['message' => $message, 'icon' => $icon, 'iconColor' => $iconColor];
    }
}

if (!function_exists('parseAndDisplayNotes')) {
    /**
     * Parses the 'notes' field and displays it as a structured history log.
     *
     * @param string|null $notes The raw notes string from the database.
     * @param bool $isPublicView If true, filters out internal-only notes.
     * @return void
     */
    function parseAndDisplayNotes(?string $notes, bool $isPublicView = false): void
    {
        if (empty(trim((string)$notes))) {
            echo '<p class="text-gray-500 italic">Belum ada catatan.</p>';
            return;
        }

        $lines = explode("\n", $notes);
        $entries = [];
        $currentEntry = [];

        foreach ($lines as $line) {
            if (strpos($line, '-----------------') !== false) {
                if (!empty($currentEntry)) {
                    $entries[] = implode("\n", $currentEntry);
                    $currentEntry = [];
                }
            } else {
                $currentEntry[] = $line;
            }
        }
        if (!empty($currentEntry)) {
            $entries[] = implode("\n", $currentEntry);
        }

        $entries = array_reverse(array_filter($entries));

        echo '<ul class="space-y-4">';

        foreach ($entries as $entry) {
            $entry = trim($entry);
            $icon = 'comment';
            $bgColor = 'bg-gray-100';
            $iconColor = 'text-gray-500';
            $title = 'Catatan';
            $details = '';
            $meta = '';

            // Check for internal notes and skip if public view
            if ($isPublicView && str_starts_with($entry, 'CATATAN INTERNAL')) {
                continue;
            }

            if (preg_match('/^CATATAN INTERNAL \((.*?)\) oleh (.*?): (.*)/s', $entry, $matches)) {
                $icon = 'admin_panel_settings'; $bgColor = 'bg-indigo-50'; $iconColor = 'text-indigo-500';
                $title = 'Catatan Internal';
                $meta = 'oleh <strong>' . htmlspecialchars($matches[2]) . '</strong> pada ' . htmlspecialchars($matches[1]);
                $details = htmlspecialchars(trim($matches[3]));
            } elseif (preg_match('/^PEMBATALAN PENGGUNAAN \((.*?)\) oleh (.*?): (.*)/s', $entry, $matches)) {
                $icon = 'undo'; $bgColor = 'bg-yellow-50'; $iconColor = 'text-yellow-600';
                $title = 'Penggunaan Dibatalkan';
                $meta = 'oleh <strong>' . htmlspecialchars($matches[2]) . '</strong> pada ' . htmlspecialchars($matches[1]);
                $details = 'Alasan: ' . htmlspecialchars(trim($matches[3]));
            } elseif (preg_match('/^LABEL DIBATALKAN \((.*?)\) oleh (.*?): (.*)/s', $entry, $matches)) {
                $icon = 'cancel'; $bgColor = 'bg-red-50'; $iconColor = 'text-red-600';
                $title = 'Label Dibatalkan';
                $meta = 'oleh <strong>' . htmlspecialchars($matches[2]) . '</strong> pada ' . htmlspecialchars($matches[1]);
                $details = 'Alasan: ' . htmlspecialchars(trim($matches[3]));
            } elseif (preg_match('/^RECALLED \(public\): (.*?) - Alasan: (.*)/s', $entry, $matches)) {
                $icon = 'report'; $bgColor = 'bg-purple-50'; $iconColor = 'text-purple-600';
                $title = 'Masalah Dilaporkan (Publik)';
                $meta = 'pada ' . htmlspecialchars($matches[1]);
                $details = 'Alasan: ' . htmlspecialchars(trim($matches[2]));
            } elseif (preg_match('/^USED: (.*?) - Oleh: (.*)/s', $entry, $matches)) {
                $icon = 'check_circle'; $bgColor = 'bg-green-50'; $iconColor = 'text-green-600';
                $title = 'Ditandai Digunakan';
                $meta = 'pada ' . htmlspecialchars($matches[1]);
                $details = 'Catatan: ' . htmlspecialchars(trim($matches[2]));
            } else {
                 // Fallback for older, unstructured notes
                $details = nl2br(htmlspecialchars($entry));
            }
            
            echo <<<HTML
            <li class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center {$bgColor}">
                        <span class="material-icons {$iconColor}">{$icon}</span>
                    </div>
                </div>
                <div class="flex-grow">
                    <p class="text-sm font-semibold text-gray-800">{$title}</p>
                    <p class="text-xs text-gray-500">{$meta}</p>
                    <div class="mt-1 text-sm text-gray-700">
                        {$details}
                    </div>
                </div>
            </li>
            HTML;
        }
        echo '</ul>';
    }
}