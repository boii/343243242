<?php
/**
 * Global Helper Functions
 *
 * Contains universally accessible helper functions for the application,
 * such as activity logging, status badge generation, and time formatting.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Helpers
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

if (!function_exists('log_activity')) {
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
}


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
            error_log("Error in timeAgo function (helpers.php): " . $e->getMessage() . " for datetime: " . $datetime);
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

/**
 * Menghitung tanggal kedaluwarsa untuk sebuah item dalam muatan.
 * Logika prioritas:
 * 1. expiry_in_days dari instrumen atau set (jika ada).
 * 2. shelf_life_days dari jenis kemasan yang dipilih untuk muatan.
 * 3. default_expiry_days dari pengaturan aplikasi.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $itemId ID dari instrumen atau set.
 * @param string $itemType Tipe item ('instrument' atau 'set').
 * @param int|null $packagingTypeId ID jenis kemasan dari muatan.
 * @return string Tanggal kedaluwarsa dalam format 'Y-m-d H:i:s'.
 */
function calculateExpiryDate(mysqli $conn, int $itemId, string $itemType, ?int $packagingTypeId): string
{
    $expiryDays = null;

    // Prioritas 1: Ambil expiry_in_days dari item/set
    $tableName = ($itemType === 'set') ? 'instrument_sets' : 'instruments';
    $primaryKey = ($itemType === 'set') ? 'set_id' : 'instrument_id';
    $stmt = $conn->prepare("SELECT expiry_in_days FROM {$tableName} WHERE {$primaryKey} = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && $result['expiry_in_days'] !== null) {
        $expiryDays = $result['expiry_in_days'];
    }
    $stmt->close();

    // Prioritas 2: Jika tidak ada, ambil dari jenis kemasan
    if ($expiryDays === null && $packagingTypeId !== null) {
        $stmt = $conn->prepare("SELECT shelf_life_days FROM packaging_types WHERE packaging_type_id = ?");
        $stmt->bind_param("i", $packagingTypeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $expiryDays = $result['shelf_life_days'];
        }
        $stmt->close();
    }

    // Prioritas 3: Jika tidak ada, ambil dari pengaturan global
    if ($expiryDays === null) {
        $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_name = 'default_expiry_days'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $expiryDays = (int)$result['setting_value'];
        }
        $stmt->close();
    }

    // Hitung tanggal kedaluwarsa jika ada hari yang ditentukan
    if ($expiryDays !== null) {
        $expiryDate = new DateTime();
        $expiryDate->modify("+{$expiryDays} days");
        return $expiryDate->format('Y-m-d H:i:s');
    }

    // Mengembalikan tanggal awal sebagai fallback jika tidak ada hari yang ditentukan
    return '1970-01-01 00:00:00';
}