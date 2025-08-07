<?php
/**
 * Edit Instrument Page - Full UI/UX Revamp
 *
 * This version implements a clean, two-column layout, separating core data
 * entry from image management for a more intuitive user experience.
 * It is consistent with other redesigned detail pages.
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

$pageTitle = "Edit Instrumen";
require_once 'header.php';

// --- Authorization Check ---
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');

if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageInstruments))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}

$instrumentId = filter_input(INPUT_GET, 'instrument_id', FILTER_VALIDATE_INT);
$instrumentData = null;
$masterTypes = [];
$masterDepartments = [];
$pageErrorMessage = '';

if (!$instrumentId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Instrumen tidak valid atau tidak disediakan.'];
    header("Location: manage_instruments.php");
    exit;
}

$conn = connectToDatabase();
if ($conn) {
    // 1. Fetch current instrument data first
    $sqlInstrument = "SELECT instrument_name, instrument_code, notes, instrument_type_id, department_id, image_filename, expiry_in_days FROM instruments WHERE instrument_id = ?";
    if ($stmt = $conn->prepare($sqlInstrument)) {
        $stmt->bind_param("i", $instrumentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $instrumentData = $result->fetch_assoc();
        } else {
            $pageErrorMessage .= " Instrumen tidak ditemukan (ID: ".htmlspecialchars((string)$instrumentId).").";
        }
        $stmt->close();
    } else {
        $pageErrorMessage .= " Gagal mempersiapkan statement data instrumen. ";
    }
    
    // 2. Fetch master data, ensuring current selections are available even if inactive
    if ($instrumentData) {
        $currentTypeId = $instrumentData['instrument_type_id'];
        $currentDeptId = $instrumentData['department_id'];

        $sqlTypes = "(SELECT type_id, type_name FROM instrument_types WHERE is_active = 1)
                     UNION
                     (SELECT type_id, type_name FROM instrument_types WHERE type_id = ?)
                     ORDER BY type_name ASC";
        if ($stmtTypes = $conn->prepare($sqlTypes)) {
            $stmtTypes->bind_param("i", $currentTypeId);
            $stmtTypes->execute();
            if($resultTypes = $stmtTypes->get_result()) {
                while ($row = $resultTypes->fetch_assoc()) $masterTypes[] = $row;
            }
            $stmtTypes->close();
        }

        $sqlDepts = "(SELECT department_id, department_name FROM departments WHERE is_active = 1)
                     UNION
                     (SELECT department_id, department_name FROM departments WHERE department_id = ?)
                     ORDER BY department_name ASC";
        if ($stmtDepts = $conn->prepare($sqlDepts)) {
            $stmtDepts->bind_param("i", $currentDeptId);
            $stmtDepts->execute();
            if ($resultDepts = $stmtDepts->get_result()) {
                while ($row = $resultDepts->fetch_assoc()) $masterDepartments[] = $row;
            }
            $stmtDepts->close();
        }
    }

    $conn->close();
} else {
    $pageErrorMessage = "Koneksi database gagal.";
}

// Get form data for repopulation if there was an error
$formData = $_SESSION['form_data_instrument_edit'] ?? $instrumentData ?? [];
unset($_SESSION['form_data_instrument_edit']);

?>
<style>
    .image-preview-container {
        width: 100%;
        height: 200px;
        background-color: #f3f4f6;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #d1d5db;
        overflow: hidden;
    }
    .image-preview-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
</style>

<main class="container mx-auto px-4 sm:px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">
            Edit Instrumen
        </h2>
        <a href="instrument_detail.php?instrument_id=<?php echo $instrumentId; ?>" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Batal & Kembali ke Detail</a>
    </div>

    <?php if ($pageErrorMessage): ?>
        <div class="alert alert-danger" role="alert"><span class="material-icons">error_outline</span><span><?php echo htmlspecialchars($pageErrorMessage); ?></span></div>
    <?php endif; ?>

    <?php if ($instrumentData): ?>
    <form id="editInstrumentForm" action="php_scripts/instrument_update.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="instrument_id" value="<?php echo htmlspecialchars((string)$instrumentId); ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 card">
                <h3 class="text-xl font-semibold text-gray-800 mb-6">Detail Data Instrumen</h3>
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="instrumentName" class="form-label">Nama Instrumen <span class="text-red-500">*</span></label>
                            <input type="text" id="instrumentName" name="instrument_name" class="form-input" required value="<?php echo htmlspecialchars($formData['instrument_name'] ?? ''); ?>">
                            <div id="similarity-warning" class="text-yellow-600 text-sm mt-1 hidden"></div>
                        </div>
                        <div>
                            <label for="instrumentCode" class="form-label">ID/Kode</label>
                            <input type="text" id="instrumentCode" name="instrument_code" class="form-input" value="<?php echo htmlspecialchars($formData['instrument_code'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="instrument_type_id" class="form-label">Tipe/Kategori <span class="text-red-500">*</span></label>
                            <select id="instrument_type_id" name="instrument_type_id" class="form-select" required>
                                <option value="" disabled>-- Pilih Tipe --</option>
                                <?php foreach ($masterTypes as $type): ?>
                                    <option value="<?php echo $type['type_id']; ?>" <?php echo (isset($formData['instrument_type_id']) && $formData['instrument_type_id'] == $type['type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="department_id" class="form-label">Departemen/Unit <span class="text-red-500">*</span></label>
                            <select id="department_id" name="department_id" class="form-select" required>
                                <option value="" disabled>-- Pilih Departemen --</option>
                                <?php foreach ($masterDepartments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>" <?php echo (isset($formData['department_id']) && $formData['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="expiry_in_days" class="form-label">Masa Kedaluwarsa Standar (Opsional)</label>
                        <input type="number" id="expiry_in_days" name="expiry_in_days" class="form-input" placeholder="Hari (e.g., 30)" min="1" value="<?php echo htmlspecialchars($formData['expiry_in_days'] ?? ''); ?>">
                        <p class="text-xs text-gray-500 mt-1">Kosongkan untuk menggunakan pengaturan global.</p>
                    </div>
                    <div>
                        <label for="notes" class="form-label">Deskripsi/Catatan</label>
                        <textarea id="notes" name="notes" rows="4" class="form-input"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-8">
                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Gambar Instrumen</h3>
                    <div class="image-preview-container">
                        <?php if (!empty($instrumentData['image_filename']) && file_exists('uploads/instruments/' . $instrumentData['image_filename'])): ?>
                            <img src="uploads/instruments/<?php echo htmlspecialchars($instrumentData['image_filename']); ?>?t=<?php echo time(); ?>" alt="Gambar Saat Ini">
                        <?php else: ?>
                            <span class="material-icons text-gray-400" style="font-size: 48px;">image_not_supported</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <label for="instrument_image" class="form-label">Ganti Gambar (Opsional)</label>
                        <input type="file" id="instrument_image" name="instrument_image" class="form-input" accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">Ukuran maks: 100 KB.</p>
                    </div>
                    <?php if (!empty($instrumentData['image_filename'])): ?>
                    <div class="mt-4 flex items-center">
                        <input type="checkbox" id="delete_image" name="delete_image" value="1" class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                        <label for="delete_image" class="ml-2 text-sm text-red-600">Hapus gambar saat ini</label>
                    </div>
                    <?php endif; ?>
                </div>
                 <div class="card">
                    <button type="submit" class="btn btn-primary w-full btn-lg py-3"><span class="material-icons mr-2">save</span>Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const instrumentNameInput = document.getElementById('instrumentName');
    const similarityWarning = document.getElementById('similarity-warning');
    const instrumentId = <?php echo json_encode($instrumentId); ?>;
    let debounceTimer;

    instrumentNameInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const name = this.value;

        if (name.length < 3) {
            similarityWarning.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`php_scripts/check_instrument_similarity.php?name=${encodeURIComponent(name)}&exclude_id=${instrumentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'exists' || data.status === 'similar') {
                        similarityWarning.textContent = data.message;
                        similarityWarning.classList.remove('hidden');
                    } else {
                        similarityWarning.classList.add('hidden');
                    }
                });
        }, 500);
    });
});
</script>

<?php require_once 'footer.php'; ?>