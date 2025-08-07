<?php
/**
 * Instrument Detail Page - Full UI/UX Revamp v2
 *
 * This version introduces a dedicated image card in the sidebar for better
 * visual hierarchy and layout consistency.
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

$pageTitle = "Detail Instrumen";
require_once 'header.php';

// Authorization Check
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');
if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageInstruments))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk melihat detail instrumen.'];
    header("Location: index.php");
    exit;
}

$instrumentId = null;
$instrumentData = null;
$setsContainingInstrument = [];
$instrumentHistory = [];
$pageErrorMessage = '';

if (isset($_GET['instrument_id']) && is_numeric($_GET['instrument_id'])) {
    $instrumentId = (int)$_GET['instrument_id'];
} else {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Instrumen tidak valid atau tidak disediakan.'];
    header("Location: manage_instruments.php");
    exit;
}

$conn = connectToDatabase();
if ($conn) {
    // 1. Fetch main instrument details including status
    $sql = "SELECT i.*, u.username as creator_username, u.full_name as creator_full_name, it.type_name, d.department_name
            FROM instruments i
            LEFT JOIN users u ON i.created_by_user_id = u.user_id
            LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
            LEFT JOIN departments d ON i.department_id = d.department_id
            WHERE i.instrument_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $instrumentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $instrumentData = $result->fetch_assoc();
        } else {
            $pageErrorMessage = "Instrumen tidak ditemukan (ID: " . htmlspecialchars((string)$instrumentId) . ").";
        }
        $stmt->close();
    } else {
        $pageErrorMessage = "Gagal mempersiapkan statement: " . $conn->error;
    }

    if ($instrumentData) {
        // 2. Fetch sets containing this instrument
        $sqlSets = "SELECT s.set_id, s.set_name, s.set_code, isi.quantity
                    FROM instrument_set_items isi
                    JOIN instrument_sets s ON isi.set_id = s.set_id
                    WHERE isi.instrument_id = ? ORDER BY s.set_name ASC";
        if ($stmtSets = $conn->prepare($sqlSets)) {
            $stmtSets->bind_param("i", $instrumentId);
            $stmtSets->execute();
            $resultSets = $stmtSets->get_result();
            while ($rowSet = $resultSets->fetch_assoc()) {
                $setsContainingInstrument[] = $rowSet;
            }
            $stmtSets->close();
        }

        // 3. Fetch status history
        $sqlHistory = "SELECT h.*, u.full_name as changer_name, u.username as changer_username
                       FROM instrument_history h
                       LEFT JOIN users u ON h.user_id = u.user_id
                       WHERE h.instrument_id = ? ORDER BY h.change_timestamp DESC";
        if ($stmtHistory = $conn->prepare($sqlHistory)) {
            $stmtHistory->bind_param("i", $instrumentId);
            $stmtHistory->execute();
            $resultHistory = $stmtHistory->get_result();
            while ($rowHistory = $resultHistory->fetch_assoc()) {
                $instrumentHistory[] = $rowHistory;
            }
            $stmtHistory->close();
        }
    }
    $conn->close();
} else {
    $pageErrorMessage = "Koneksi database gagal.";
}

?>
<main class="container mx-auto px-6 py-8">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-semibold text-gray-700">
            Detail Instrumen
        </h2>
        <a href="manage_instruments.php" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Daftar Instrumen</a>
    </div>

    <?php if ($pageErrorMessage): ?>
        <div class="alert alert-danger" role="alert"><span class="material-icons">error_outline</span><span><?php echo htmlspecialchars($pageErrorMessage); ?></span></div>
    <?php endif; ?>

    <?php if ($instrumentData): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            <div class="card">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($instrumentData['instrument_name']); ?></h3>
                        <p class="font-mono text-sm text-gray-500"><?php echo htmlspecialchars($instrumentData['instrument_code'] ?? 'Tidak ada kode'); ?></p>
                    </div>
                    <?php $statusInfo = getUniversalStatusBadge($instrumentData['status']); ?>
                    <span class="status-badge-lg <?php echo $statusInfo['class']; ?> whitespace-nowrap"><?php echo $statusInfo['text']; ?></span>
                </div>
                 <dl class="detail-grid text-sm">
                    <dt>Tipe/Kategori:</dt><dd><?php echo htmlspecialchars($instrumentData['type_name'] ?? '-'); ?></dd>
                    <dt>Departemen/Unit:</dt><dd><?php echo htmlspecialchars($instrumentData['department_name'] ?? '-'); ?></dd>
                    <dt>Dibuat Oleh:</dt><dd><?php echo htmlspecialchars($instrumentData['creator_full_name'] ?? ($instrumentData['creator_username'] ?? 'N/A')); ?></dd>
                    <dt>Tanggal Dibuat:</dt><dd><?php echo (new DateTime($instrumentData['created_at']))->format('d M Y, H:i:s'); ?></dd>
                    <dt>Terakhir Diperbarui:</dt><dd><?php echo (new DateTime($instrumentData['updated_at']))->format('d M Y, H:i:s'); ?></dd>
                    <?php if (!empty($instrumentData['notes'])): ?>
                        <dt>Catatan:</dt><dd class="whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($instrumentData['notes'])); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            
            <div class="card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Bagian dari Set Berikut (<?php echo count($setsContainingInstrument); ?>)</h3>
                <?php if (!empty($setsContainingInstrument)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left">Nama Set</th><th class="px-4 py-2 text-left">Kode Set</th><th class="px-4 py-2 text-center">Kuantitas</th></tr></thead>
                            <tbody>
                                <?php foreach ($setsContainingInstrument as $set): ?>
                                    <tr class="border-b"><td class="px-4 py-2"><a href="set_detail.php?set_id=<?php echo $set['set_id']; ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($set['set_name']); ?></a></td><td class="px-4 py-2 font-mono"><?php echo htmlspecialchars($set['set_code'] ?? '-'); ?></td><td class="px-4 py-2 text-center font-semibold"><?php echo htmlspecialchars((string)$set['quantity']); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?><p class="text-gray-600">Instrumen ini tidak tergabung dalam set manapun.</p><?php endif; ?>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-8">
            <div class="card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Gambar Instrumen</h3>
                <div class="w-full h-48 bg-gray-100 rounded-md flex items-center justify-center">
                    <?php if (!empty($instrumentData['image_filename']) && file_exists('uploads/instruments/' . $instrumentData['image_filename'])): ?>
                        <img src="uploads/instruments/<?php echo htmlspecialchars($instrumentData['image_filename']); ?>?t=<?php echo time(); ?>" alt="Gambar Instrumen" class="max-h-full max-w-full object-contain">
                    <?php else: ?>
                        <span class="material-icons text-gray-400" style="font-size: 48px;">image_not_supported</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Aksi</h3>
                <div class="space-y-4">
                    <a href="instrument_edit.php?instrument_id=<?php echo $instrumentId; ?>" class="btn btn-primary w-full"><span class="material-icons mr-2">edit</span>Edit Detail Instrumen</a>
                    <div class="border-t pt-4">
                        <form action="php_scripts/instrument_update_status.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="instrument_id" value="<?php echo $instrumentId; ?>">
                            <div class="mb-3">
                                <label for="new_status" class="form-label font-medium">Ubah Status Menjadi:</label>
                                <select name="new_status" id="new_status" class="form-select">
                                    <option value="tersedia" <?php echo $instrumentData['status'] === 'tersedia' ? 'disabled' : ''; ?>>Tersedia</option>
                                    <option value="perbaikan" <?php echo $instrumentData['status'] === 'perbaikan' ? 'disabled' : ''; ?>>Perbaikan</option>
                                    <option value="rusak" <?php echo $instrumentData['status'] === 'rusak' ? 'disabled' : ''; ?>>Rusak</option>
                                    <option value="sterilisasi" <?php echo $instrumentData['status'] === 'sterilisasi' ? 'disabled' : ''; ?>>Sterilisasi</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status_notes" class="form-label">Catatan (Wajib jika 'Rusak')</label>
                                <textarea name="notes" id="status_notes" rows="2" class="form-input" placeholder="Contoh: Dikirim ke teknisi..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning w-full"><span class="material-icons mr-2">sync</span>Perbarui Status</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3 class="text-xl font-semibold text-gray-800 mb-3">Riwayat Status</h3>
                <?php if (!empty($instrumentHistory)): ?>
                    <ul class="space-y-4">
                        <?php foreach ($instrumentHistory as $history): ?>
                            <li class="flex space-x-3">
                                <div class="flex-shrink-0">
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center bg-gray-200 text-gray-600"><span class="material-icons text-lg">history</span></span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Status diubah menjadi "<?php echo htmlspecialchars(getUniversalStatusBadge($history['changed_to_status'])['text']); ?>"</p>
                                    <p class="text-sm text-gray-500">oleh <?php echo htmlspecialchars($history['changer_name'] ?? ($history['changer_username'] ?? 'Sistem')); ?></p>
                                    <?php if (!empty($history['notes'])): ?>
                                        <p class="mt-1 text-sm text-gray-600 bg-gray-50 p-2 rounded-md">"<?php echo nl2br(htmlspecialchars($history['notes'])); ?>"</p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mt-1" title="<?php echo (new DateTime($history['change_timestamp']))->format('d M Y, H:i:s'); ?>"><?php echo timeAgo($history['change_timestamp']); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?><p class="text-gray-600">Belum ada riwayat perubahan status.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>
<?php
require_once 'footer.php';
?>