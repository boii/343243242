<?php
/**
 * Create New Instrument Set - Step 1 (UI/UX Revamp)
 *
 * Provides a dedicated page for entering the basic details of a new set,
 * with a UI consistent with the redesigned set_edit.php page.
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

$pageTitle = "Buat Set Baru - Langkah 1";
require_once 'header.php';

// Authorization check
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');
if (!($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanManageSets))) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk membuat set baru.'];
    header("Location: index.php");
    exit;
}

// Get form data for repopulation if there was an error
$formData = $_SESSION['form_data_set_add'] ?? [];
unset($_SESSION['form_data_set_add']);

?>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Buat Set Baru: Langkah 1 dari 2</h2>
        <a href="manage_sets.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Batal & Kembali
        </a>
    </div>

    <div class="card max-w-2xl mx-auto">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Informasi Dasar Set</h3>
        <p class="text-sm text-gray-500 mb-6">Isi detail dasar untuk set ini. Setelah disimpan, Anda akan diarahkan untuk memilih isi instrumennya.</p>

        <form id="createSetForm" action="php_scripts/set_add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="set_name" class="form-label">Nama Set <span class="text-red-500">*</span></label>
                    <input type="text" id="set_name" name="set_name" class="form-input" required value="<?php echo htmlspecialchars($formData['set_name'] ?? ''); ?>" placeholder="Contoh: Set Bedah Mayor">
                </div>
                <div>
                    <label for="set_code" class="form-label">Kode Set <span class="text-red-500">*</span></label>
                    <input type="text" id="set_code" name="set_code" class="form-input" required value="<?php echo htmlspecialchars($formData['set_code'] ?? ''); ?>" placeholder="Dibuat otomatis, bisa diubah">
                </div>
                 <div>
                    <label for="expiry_in_days" class="form-label">Masa Kedaluwarsa Standar (Opsional)</label>
                    <input type="number" id="expiry_in_days" name="expiry_in_days" class="form-input" placeholder="Hari (e.g., 30)" min="1" value="<?php echo htmlspecialchars((string)($formData['expiry_in_days'] ?? '')); ?>">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan untuk memakai aturan terpendek dari isi set.</p>
                </div>
                <div>
                    <label for="special_instructions" class="form-label">Instruksi Khusus (Opsional)</label>
                    <textarea id="special_instructions" name="special_instructions" rows="4" class="form-input" placeholder="Contoh: Perhatikan ujung gunting, jangan ditumpuk."><?php echo htmlspecialchars($formData['special_instructions'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="text-right mt-8">
                <button type="submit" class="btn btn-primary btn-lg py-3 px-6">
                    <span class="material-icons mr-2">arrow_forward</span>Simpan & Lanjutkan
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const setCodeInput = document.getElementById('set_code');
        // Hanya generate kode jika field kosong (misalnya saat halaman pertama kali dibuka tanpa ada error sebelumnya)
        if (setCodeInput && setCodeInput.value === '') {
            const timestamp = Date.now().toString();
            // Menggunakan 8 digit terakhir dari timestamp untuk keunikan yang baik
            const uniquePart = timestamp.substring(timestamp.length - 8);
            setCodeInput.value = `SET-${uniquePart}`;
        }
    });
</script>

<?php
require_once 'footer.php';
?>