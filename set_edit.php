<?php
/**
 * Edit Instrument Set Page (UI/UX Revamp)
 *
 * Provides a full-featured, two-column interface for editing a set's
 * details and managing its instrument contents using a single, unified table.
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

$pageTitle = "Edit Set Instrumen";
require_once 'header.php';

// Authorization check
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');
if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageSets))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengedit set.'];
    header("Location: index.php");
    exit;
}

$setId = filter_input(INPUT_GET, 'set_id', FILTER_VALIDATE_INT);
$isNewSet = isset($_GET['new']) && $_GET['new'] === 'true';

if (!$setId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Set tidak valid.'];
    header("Location: manage_sets.php");
    exit;
}

// --- Data Fetching ---
$setData = null;
$allInstrumentsGrouped = [];
$currentSetInstruments = [];
$pageErrorMessage = '';

$conn = connectToDatabase();
if ($conn) {
    // 1. Fetch set details
    $stmtSet = $conn->prepare("SELECT set_name, set_code, special_instructions, expiry_in_days FROM instrument_sets WHERE set_id = ?");
    $stmtSet->bind_param("i", $setId);
    $stmtSet->execute();
    $resultSet = $stmtSet->get_result();
    if ($resultSet && $resultSet->num_rows === 1) {
        $setData = $resultSet->fetch_assoc();
    } else {
        $pageErrorMessage = "Set tidak ditemukan.";
    }
    $stmtSet->close();

    if ($setData) {
        // 2. Fetch all available instruments, grouped by type
        $sqlAllInstruments = "SELECT i.instrument_id, i.instrument_name, i.instrument_code, it.type_name 
                              FROM instruments i
                              LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
                              WHERE i.status = 'tersedia'
                              ORDER BY it.type_name, i.instrument_name ASC";
        if ($resultInstruments = $conn->query($sqlAllInstruments)) {
            while ($instrument = $resultInstruments->fetch_assoc()) {
                $typeName = $instrument['type_name'] ?? 'Lain-lain';
                $allInstrumentsGrouped[$typeName][] = $instrument;
            }
        } else {
            $pageErrorMessage .= " Gagal memuat daftar instrumen.";
        }

        // 3. Fetch current items in this set
        $sqlCurrentItems = "SELECT isi.instrument_id, i.instrument_name, i.instrument_code, isi.quantity 
                            FROM instrument_set_items isi
                            JOIN instruments i ON isi.instrument_id = i.instrument_id
                            WHERE isi.set_id = ?";
        if ($stmtCurrent = $conn->prepare($sqlCurrentItems)) {
            $stmtCurrent->bind_param("i", $setId);
            $stmtCurrent->execute();
            $resultCurrent = $stmtCurrent->get_result();
            while ($item = $resultCurrent->fetch_assoc()) {
                $currentSetInstruments[$item['instrument_id']] = $item;
            }
            $stmtCurrent->close();
        }
    }

    $conn->close();
} else {
    $pageErrorMessage = "Koneksi database gagal.";
}

$formData = $_SESSION['form_data_set_edit'] ?? $setData;
unset($_SESSION['form_data_set_edit']);

?>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">
            <?php echo $isNewSet ? 'Langkah 2: Pilih Isi Set' : 'Edit Set'; ?>: <?php echo htmlspecialchars($setData['set_name'] ?? 'Tidak Ditemukan'); ?>
        </h2>
    </div>

    <?php if ($pageErrorMessage): ?>
        <div class="alert alert-danger" role="alert">
            <span class="material-icons">error_outline</span>
            <span><?php echo htmlspecialchars($pageErrorMessage); ?></span>
        </div>
    <?php elseif ($setData): ?>
        <form id="editSetForm" action="php_scripts/set_update.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="set_id" value="<?php echo htmlspecialchars((string)$setId); ?>">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-6">
                    <div class="card">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Detail Set</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="set_name" class="form-label">Nama Set <span class="text-red-500">*</span></label>
                                <input type="text" id="set_name" name="set_name" class="form-input" required value="<?php echo htmlspecialchars($formData['set_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label for="set_code" class="form-label">Kode Set <span class="text-red-500">*</span></label>
                                <input type="text" id="set_code" name="set_code" class="form-input" required value="<?php echo htmlspecialchars($formData['set_code'] ?? ''); ?>">
                            </div>
                            <div>
                                <label for="expiry_in_days" class="form-label">Masa Kedaluwarsa Standar (Opsional)</label>
                                <input type="number" id="expiry_in_days" name="expiry_in_days" class="form-input" placeholder="Hari (e.g., 30)" min="1" value="<?php echo htmlspecialchars((string)($formData['expiry_in_days'] ?? '')); ?>">
                                <p class="text-xs text-gray-500 mt-1">Kosongkan untuk memakai aturan terpendek dari isi set.</p>
                            </div>
                            <div>
                                <label for="special_instructions" class="form-label">Instruksi Khusus (Opsional)</label>
                                <textarea id="special_instructions" name="special_instructions" rows="4" class="form-input"><?php echo htmlspecialchars($formData['special_instructions'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Pilih Isi Instrumen</h3>
                    <div class="mb-4">
                        <label for="instrumentSearch" class="form-label">Cari Instrumen Tersedia</label>
                        <input type="text" id="instrumentSearch" class="form-input" placeholder="Ketik nama atau kode instrumen untuk memfilter...">
                    </div>
                    
                    <div class="overflow-y-auto border rounded-lg" style="max-height: 500px;">
                        <table class="w-full table-auto" id="instrumentPickerTable">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="py-2 px-4 text-center w-12"><input type="checkbox" id="selectAllCheckbox" title="Pilih Semua"></th>
                                    <th class="py-2 px-4 text-left">Nama Instrumen</th>
                                    <th class="py-2 px-4 text-left w-32">Kuantitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allInstrumentsGrouped)): ?>
                                    <tr><td colspan="3" class="text-center p-4 text-gray-500">Tidak ada instrumen yang tersedia.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($allInstrumentsGrouped as $type => $instruments): ?>
                                        <tr class="bg-gray-50 font-bold text-sm">
                                            <td colspan="3" class="px-4 py-2"><?php echo htmlspecialchars($type); ?></td>
                                        </tr>
                                        <?php foreach ($instruments as $instrument): 
                                            $instrumentId = $instrument['instrument_id'];
                                            $isChecked = isset($currentSetInstruments[$instrumentId]);
                                            $quantity = $isChecked ? $currentSetInstruments[$instrumentId]['quantity'] : 1;
                                        ?>
                                            <tr class="instrument-row border-t" data-search-term="<?php echo htmlspecialchars(strtolower($instrument['instrument_name'] . ' ' . $instrument['instrument_code'])); ?>">
                                                <td class="px-4 py-2 text-center">
                                                    <input type="checkbox" name="instruments[]" value="<?php echo $instrumentId; ?>" class="instrument-checkbox h-5 w-5" <?php echo $isChecked ? 'checked' : ''; ?>>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <label for="inst_<?php echo $instrumentId; ?>" class="font-medium"><?php echo htmlspecialchars($instrument['instrument_name']); ?></label>
                                                    <div class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($instrument['instrument_code'] ?? '-'); ?></div>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <input type="number" name="quantities[<?php echo $instrumentId; ?>]" id="inst_<?php echo $instrumentId; ?>" class="form-input form-input-sm w-24 text-center" value="<?php echo $quantity; ?>" min="1" <?php echo $isChecked ? '' : 'disabled'; ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end items-center gap-3">
                <a href="manage_sets.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons mr-2">save</span>Simpan Perubahan pada Set
                </button>
            </div>
        </form>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('instrumentPickerTable');
    if (!table) return;

    const tableBody = table.querySelector('tbody');
    const searchInput = document.getElementById('instrumentSearch');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    // Fungsi untuk mengaktifkan/menonaktifkan input kuantitas berdasarkan status checkbox
    const toggleQuantityInput = (checkbox) => {
        const row = checkbox.closest('tr');
        const quantityInput = row.querySelector('input[type="number"]');
        if (quantityInput) {
            quantityInput.disabled = !checkbox.checked;
        }
    };

    // Event listener untuk seluruh body tabel
    tableBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('instrument-checkbox')) {
            toggleQuantityInput(e.target);
        }
    });

    // Event listener untuk checkbox "Pilih Semua"
    selectAllCheckbox.addEventListener('change', () => {
        tableBody.querySelectorAll('.instrument-checkbox').forEach(checkbox => {
            // Hanya ubah checkbox yang terlihat (tidak terfilter)
            if (checkbox.closest('tr').style.display !== 'none') {
                checkbox.checked = selectAllCheckbox.checked;
                toggleQuantityInput(checkbox);
            }
        });
    });

    // Fungsi pencarian
    searchInput.addEventListener('input', () => {
        const filter = searchInput.value.toLowerCase().trim();
        tableBody.querySelectorAll('tr.instrument-row').forEach(row => {
            const searchTerm = row.dataset.searchTerm || '';
            row.style.display = searchTerm.includes(filter) ? '' : 'none';
        });
    });

    // Inisialisasi status input kuantitas saat halaman dimuat
    tableBody.querySelectorAll('.instrument-checkbox').forEach(toggleQuantityInput);
});
</script>

<?php
require_once 'footer.php';
?>