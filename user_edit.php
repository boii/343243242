<?php
/**
 * Edit User Page (Full UI/UX Revamp)
 *
 * This version refactors the entire layout into a cleaner, more logical
 * two-column grid. It also switches to the automated breadcrumb system
 * and enforces stricter access control for supervisors.
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

$pageTitle = "Edit Pengguna";
require_once 'header.php'; // Includes session check, $app_settings, $userRole, $csrfToken etc.

// Access control
if (!in_array($userRole, ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}

$userIdToEdit = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$userData = null;
$pageErrorMessage = '';

if (!$userIdToEdit) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Pengguna tidak valid atau tidak disediakan untuk diedit.'];
    header("Location: user_management.php");
    exit;
}

if (isset($_SESSION['user_id']) && $userIdToEdit === (int)$_SESSION['user_id']) {
    $_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Anda tidak dapat mengedit akun Anda sendiri melalui halaman ini. Gunakan halaman Profil.'];
    header("Location: user_management.php");
    exit;
}

// Fetch user data
$conn = connectToDatabase();
if ($conn) {
    $sql = "SELECT username, full_name, email, role FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userIdToEdit);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $userData = $result->fetch_assoc();
            // Supervisor cannot edit other supervisors or admins
            if ($userRole === 'supervisor' && in_array($userData['role'], ['admin', 'supervisor'])) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengedit pengguna dengan peran ini.'];
                header("Location: user_management.php");
                exit;
            }
        } else {
            $pageErrorMessage = "Pengguna tidak ditemukan (ID: " . htmlspecialchars((string)$userIdToEdit) . ").";
        }
        $stmt->close();
    } else {
        $pageErrorMessage = "Gagal mempersiapkan statement pengambilan data pengguna: " . $conn->error;
    }
    $conn->close();
} else {
    $pageErrorMessage = "Koneksi database gagal.";
}

render_breadcrumbs($userData['username'] ?? '...');

// Get form data for repopulation if there was an error
$formData = $_SESSION['form_data_user_edit'] ?? $userData ?? [];
unset($_SESSION['form_data_user_edit']); 

if (empty($userData) && empty($pageErrorMessage) && empty($formData) ) {
     $pageErrorMessage = "Data pengguna tidak dapat dimuat atau tidak ditemukan.";
}
?>
<main class="container mx-auto px-4 sm:px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">
            Edit Pengguna: <?php echo htmlspecialchars($formData['username'] ?? ($userData['username'] ?? 'Tidak Ditemukan')); ?>
        </h2>
        <a href="user_management.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Kembali ke Manajemen Pengguna
        </a>
    </div>

    <?php if (!empty($pageErrorMessage)): ?>
         <div class="alert alert-danger" role="alert">
            <span class="material-icons">error_outline</span>
            <span><?php echo htmlspecialchars($pageErrorMessage); ?></span>
        </div>
    <?php elseif ($userIdToEdit && !empty($formData)): ?>
        <div class="card max-w-4xl mx-auto">
            <form id="editUserForm" action="php_scripts/user_update.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string)$userIdToEdit); ?>">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="fullName" class="form-label">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="fullName" name="full_name" class="form-input" required value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="usernameInput" class="form-label">Username <span class="text-sm text-gray-500">(Tidak dapat diubah)</span></label>
                            <input type="text" id="usernameInput" name="username_display" class="form-input bg-gray-100" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" readonly>
                        </div>
                        <div>
                            <label for="email" class="form-label">Email (Opsional)</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="e.g., john.doe@example.com" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="userRoleSelect" class="form-label">Peran <span class="text-red-500">*</span></label>
                            <select id="userRoleSelect" name="role" class="form-select" required>
                                <option value="staff" <?php echo (isset($formData['role']) && $formData['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                                <option value="supervisor" <?php echo (isset($formData['role']) && $formData['role'] === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                <?php if ($userRole === 'admin'): ?>
                                <option value="admin" <?php echo (isset($formData['role']) && $formData['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                         <label class="form-label font-semibold">Ubah Password (Opsional)</label>
                         <p class="text-xs text-gray-500 mb-2">Kosongkan jika tidak ingin mengubah password.</p>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                             <div>
                                <label for="password" class="form-label text-sm">Password Baru</label>
                                <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter">
                            </div>
                            <div>
                                <label for="confirmPassword" class="form-label text-sm">Konfirmasi Password Baru</label>
                                <input type="password" id="confirmPassword" name="confirm_password" class="form-input" placeholder="Ulangi password baru">
                            </div>
                         </div>
                         <div id="passwordMismatchErrorEdit" class="text-red-500 text-sm mt-2 hidden">Password baru tidak cocok.</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <a href="user_management.php" class="btn bg-gray-200 text-gray-700 hover:bg-gray-300">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons mr-2">save</span>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
         <div class="alert alert-danger" role="alert">
            <span class="material-icons">error_outline</span>
            <span>Data pengguna tidak dapat ditampilkan untuk diedit. Silakan coba lagi atau kembali ke daftar.</span>
        </div>
    <?php endif; ?>
</main>
<script>
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        const passwordInputEdit = document.getElementById('password');
        const confirmPasswordInputEdit = document.getElementById('confirmPassword');
        const passwordMismatchErrorEdit = document.getElementById('passwordMismatchErrorEdit');

        function validatePasswordsEdit() {
            if (passwordInputEdit.value !== '' || confirmPasswordInputEdit.value !== '') {
                if (passwordInputEdit.value !== confirmPasswordInputEdit.value) {
                    passwordMismatchErrorEdit.classList.remove('hidden');
                    return false;
                }
            }
            passwordMismatchErrorEdit.classList.add('hidden');
            return true;
        }

        editUserForm.addEventListener('submit', function(event) {
            if (!validatePasswordsEdit()) {
                event.preventDefault(); 
            }
        });
        if(passwordInputEdit) passwordInputEdit.addEventListener('input', validatePasswordsEdit);
        if(confirmPasswordInputEdit) confirmPasswordInputEdit.addEventListener('input', validatePasswordsEdit);
    }
</script>
<?php
require_once 'footer.php';
?>