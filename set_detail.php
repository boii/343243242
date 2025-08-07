<?php
/**
 * Instrument Set Detail Page (with Breadcrumbs & History)
 *
 * This version uses the standardized grid layout, is cleaned of inline styles,
 * includes breadcrumb navigation, and now features a change history log.
 * Supervisor access is correctly granted.
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

$pageTitle = "Detail Set Instrumen";
require_once 'header.php'; // Includes session check, CSRF token, etc.

// Ambil pengaturan hak akses staff dari $app_settings global
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');

if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageSets))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk melihat detail set.'];
    header("Location: index.php");
    exit;
}

$setId = null;
$setData = null;
$setInstruments = []; 
$setHistory = []; // Variabel untuk menampung riwayat
$pageErrorMessage = '';

if (isset($_GET['set_id']) && is_numeric($_GET['set_id'])) {
    $setId = (int)$_GET['set_id'];
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Set tidak valid atau tidak disediakan.'];
    header("Location: manage_sets.php");
    exit;
}

// Fetch set data and its items
$conn = connectToDatabase();
if ($conn) {
    // Fetch set details
    $sqlSet = "SELECT s.set_id, s.set_name, s.set_code, s.special_instructions, s.created_at, s.updated_at, u.username as creator_username, u.full_name as creator_full_name
               FROM instrument_sets s
               LEFT JOIN users u ON s.created_by_user_id = u.user_id
               WHERE s.set_id = ?";
    if ($stmtSet = $conn->prepare($sqlSet)) {
        $stmtSet->bind_param("i", $setId);
        $stmtSet->execute();
        $resultSet = $stmtSet->get_result();
        if ($resultSet && $resultSet->num_rows === 1) {
            $setData = $resultSet->fetch_assoc();
        } else {
            $pageErrorMessage = "Set instrumen tidak ditemukan.";
        }
        $stmtSet->close();
    } else {
        $pageErrorMessage = "Gagal mempersiapkan statement untuk mengambil data set: " . $conn->error;
    }

    // Fetch instruments in this set
    if (!$pageErrorMessage && $setData) {
        $sqlSetItems = "SELECT i.instrument_id, i.instrument_name, i.instrument_code, it.type_name, isi.quantity
                        FROM instrument_set_items isi
                        JOIN instruments i ON isi.instrument_id = i.instrument_id
                        LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
                        WHERE isi.set_id = ?
                        ORDER BY i.instrument_name ASC";
        if ($stmtItems = $conn->prepare($sqlSetItems)) {
            $stmtItems->bind_param("i", $setId);
            $stmtItems->execute();
            $resultItems = $stmtItems->get_result();
            while ($rowItem = $resultItems->fetch_assoc()) {
                $setInstruments[] = $rowItem;
            }
            $stmtItems->close();
        } else {
            $pageErrorMessage .= " Gagal mengambil instrumen dalam set: " . $conn->error;
        }
    }
    
    // Fetch Change History
    if (!$pageErrorMessage && $setData) {
        $sqlHistory = "SELECT al.action_type, al.details, al.log_timestamp, u.full_name as actor_name, u.username as actor_username
                       FROM activity_log al
                       LEFT JOIN users u ON al.user_id = u.user_id
                       WHERE al.target_type = 'set' AND al.target_id = ?
                       ORDER BY al.log_timestamp DESC
                       LIMIT 10"; // Batasi untuk performa
        if ($stmtHistory = $conn->prepare($sqlHistory)) {
            $stmtHistory->bind_param("i", $setId);
            $stmtHistory->execute();
            $resultHistory = $stmtHistory->get_result();
            while ($rowHistory = $resultHistory->fetch_assoc()) {
                $setHistory[] = $rowHistory;
            }
            $stmtHistory->close();
        }
    }
    
    $conn->close();
} else {
    $pageErrorMessage = "Koneksi database gagal.";
}

// Panggil fungsi breadcrumb setelah mendapatkan data
render_breadcrumbs($setData['set_name'] ?? 'Detail Set');
?>
<main class="container mx-auto px-6 py-8">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h2 class="text-2xl font-semibold text-gray-700">
            Detail Set: <?php echo $setData ? htmlspecialchars($setData['set_name']) : 'Tidak Ditemukan'; ?>
        </h2>
        <div class="flex space-x-2">
            <a href="manage_sets.php" class="btn btn-secondary">
                <span class="material-icons mr-2">arrow_back</span>Kembali ke Daftar Set
            </a>
            <?php if ($setData && ($userRole === 'admin' || $userRole === 'supervisor' || $staffCanManageSets)): ?>
            <a href="set_edit.php?set_id=<?php echo $setId; ?>" class="btn btn-primary">
                <span class="material-icons mr-2">edit</span>Edit Set Ini
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($pageErrorMessage)): ?>
         <div class="alert alert-danger" role="alert">
            <span class="material-icons">error_outline</span>
            <span><?php echo htmlspecialchars($pageErrorMessage); ?></span>
        </div>
    <?php elseif ($setData): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">Informasi Set</h3>
                    <dl class="detail-grid text-sm">
                        <dt>Nama Set:</dt>
                        <dd><?php echo htmlspecialchars($setData['set_name']); ?></dd>
                        <dt>ID/Kode Set:</dt>
                        <dd class="font-mono"><?php echo htmlspecialchars($setData['set_code'] ?? '-'); ?></dd>
                        <dt>Dibuat Oleh:</dt>
                        <dd><?php echo htmlspecialchars($setData['creator_full_name'] ?? ($setData['creator_username'] ?? 'N/A')); ?></dd>
                        <dt>Tanggal Dibuat:</dt>
                        <dd><?php echo (new DateTime($setData['created_at']))->format('d M Y, H:i'); ?></dd>
                        <dt>Diperbarui:</dt>
                        <dd><?php echo (new DateTime($setData['updated_at']))->format('d M Y, H:i'); ?></dd>
                        <?php if (!empty($setData['special_instructions'])): ?>
                            <dt>Instruksi Khusus:</dt>
                            <dd class="whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($setData['special_instructions'])); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>

                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">Isi Set (<?php echo count($setInstruments); ?> jenis item)</h3>
                     <?php if (!empty($setInstruments)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Nama Instrumen</th>
                                        <th class="px-4 py-2 text-left">Tipe</th>
                                        <th class="px-4 py-2 text-center">Kuantitas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($setInstruments as $instrument): ?>
                                        <tr class="border-b">
                                            <td class="px-4 py-2">
                                                <a href="instrument_detail.php?instrument_id=<?php echo $instrument['instrument_id']; ?>" class="text-blue-600 hover:underline">
                                                    <?php echo htmlspecialchars($instrument['instrument_name']); ?>
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-gray-500"><?php echo htmlspecialchars($instrument['type_name'] ?? '-'); ?></td>
                                            <td class="px-4 py-2 text-center font-semibold"><?php echo htmlspecialchars((string)$instrument['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600">Tidak ada instrumen yang terdaftar dalam set ini.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-3 pb-2 border-b">Riwayat Perubahan</h3>
                    <?php if (!empty($setHistory)): ?>
                        <ul class="space-y-4">
                            <?php foreach ($setHistory as $history): ?>
                                <li class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center bg-gray-200 text-gray-600">
                                            <span class="material-icons text-lg">
                                                <?php echo ($history['action_type'] === 'CREATE_SET') ? 'add_circle' : 'edit_note'; ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo ($history['action_type'] === 'CREATE_SET') ? 'Set Dibuat' : 'Set Diperbarui'; ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            oleh <?php echo htmlspecialchars($history['actor_name'] ?? ($history['actor_username'] ?? 'Sistem')); ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1" title="<?php echo (new DateTime($history['log_timestamp']))->format('d M Y, H:i:s'); ?>">
                                            <?php echo timeAgo($history['log_timestamp']); ?>
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-600">Belum ada riwayat perubahan untuk set ini.</p>
                    <?php endif; ?>
                </div>
            </div>
            </div>
    <?php endif; ?>
</main>
<?php
require_once 'footer.php';
?>