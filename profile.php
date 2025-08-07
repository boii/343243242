<?php
/**
 * User Profile Page (Revamped for Elegance & Consistency)
 *
 * Allows logged-in users to view and update their profile information.
 * This version uses a more elegant two-column card layout and removes
 * non-functional display fields.
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

$pageTitle = "Profil Saya";
require_once 'header.php'; // Includes session check, CSRF token etc.

// Jika pengguna tidak login, header.php akan mengarahkan ke login.
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserData = null;
$pageErrorMessage = '';

if (!$currentUserId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Sesi tidak valid atau pengguna tidak ditemukan.'];
    header("Location: login.php");
    exit;
}

// Fetch current user data
$conn = connectToDatabase();
if ($conn) {
    $sql = "SELECT user_id, username, full_name, email, role, created_at FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $currentUserData = $result->fetch_assoc();
        } else {
            $pageErrorMessage = "Gagal memuat data profil pengguna.";
        }
        $stmt->close();
    } else {
        $pageErrorMessage = "Gagal mempersiapkan statement pengambilan data profil: " . $conn->error;
    }
    $conn->close();
} else {
    $pageErrorMessage = "Koneksi database gagal.";
}

// Get form data for repopulation if there was an error
$formData = $_SESSION['form_data_profile'] ?? $currentUserData ?? [];
unset($_SESSION['form_data_profile']);

if (empty($currentUserData) && empty($pageErrorMessage) && empty($formData)) {
     $pageErrorMessage = "Data profil tidak dapat dimuat.";
}

?>
<main class="container mx-auto px-4 sm:px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Profil Saya</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Kembali ke Dashboard
        </a>
    </div>

    <?php if (!empty($pageErrorMessage)): ?>
         <div class="alert alert-danger" role="alert">
            <span class="material-icons">error_outline</span>
            <span><?php echo htmlspecialchars($pageErrorMessage); ?></span>
        </div>
    <?php elseif (!empty($formData)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="card">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">Informasi Akun</h3>
                    <dl class="detail-grid">
                        <dt>Username:</dt>
                        <dd class="font-mono"><?php echo htmlspecialchars($formData['username'] ?? 'N/A'); ?></dd>
                        
                        <dt>Nama Lengkap:</dt>
                        <dd><?php echo htmlspecialchars($formData['full_name'] ?? 'N/A'); ?></dd>
                        
                        <dt>Email:</dt>
                        <dd><?php echo htmlspecialchars($formData['email'] ?? 'Tidak diatur'); ?></dd>
                        
                        <dt>Peran:</dt>
                        <dd><span class="role-badge role-<?php echo htmlspecialchars($formData['role'] ?? 'staff'); ?>"><?php echo htmlspecialchars($formData['role'] ?? 'N/A'); ?></span></dd>
                        
                        <dt>Bergabung:</dt>
                        <dd><?php echo isset($formData['created_at']) ? (new DateTime($formData['created_at']))->format('d F Y') : 'N/A'; ?></dd>
                    </dl>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="card">
                    <form id="updateProfileForm" action="php_scripts/profile_update.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <section class="mb-6">
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Ubah Detail Profil</h4>
                            <div class="space-y-4">
                                <div>
                                    <label for="fullNameProfile" class="form-label">Nama Lengkap</label>
                                    <input type="text" id="fullNameProfile" name="full_name" class="form-input" required value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label for="emailProfile" class="form-label">Email</label>
                                    <input type="email" id="emailProfile" name="email" class="form-input" placeholder="e.g., nama@contoh.com" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </section>

                        <hr class="my-6">

                        <section>
                            <h4 class="text-lg font-semibold text-gray-700 mb-3">Ubah Password</h4>
                            <p class="text-xs text-gray-500 mb-3">Kosongkan semua kolom password jika Anda tidak ingin mengubahnya.</p>
                            <div class="space-y-4">
                                <div>
                                    <label for="currentPassword" class="form-label">Password Saat Ini (Wajib jika ubah password)</label>
                                    <input type="password" id="currentPassword" name="current_password" class="form-input" placeholder="Masukkan password saat ini">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="newPassword" class="form-label">Password Baru</label>
                                        <input type="password" id="newPassword" name="new_password" class="form-input" placeholder="Minimal 6 karakter">
                                    </div>
                                    <div>
                                        <label for="confirmNewPassword" class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" id="confirmNewPassword" name="confirm_new_password" class="form-input" placeholder="Ulangi password baru">
                                    </div>
                                </div>
                                <div id="passwordProfileMismatchError" class="text-red-500 text-sm mt-1 hidden">Password baru tidak cocok.</div>
                            </div>
                        </section>

                        <div class="mt-8 text-right">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-icons mr-2">save</span>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
         <div class="alert alert-danger" role="alert">
            <span class="material-icons">error_outline</span>
            <span>Data profil tidak dapat ditampilkan. Silakan coba lagi atau hubungi administrator.</span>
        </div>
    <?php endif; ?>
</main>
<script>
    const updateProfileForm = document.getElementById('updateProfileForm');
    if (updateProfileForm) {
        const currentPasswordInput = document.getElementById('currentPassword');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
        const passwordProfileMismatchError = document.getElementById('passwordProfileMismatchError');

        function validateProfilePasswords() {
            // Hanya validasi jika password baru diisi
            if (newPasswordInput.value !== '' || confirmNewPasswordInput.value !== '') {
                if (newPasswordInput.value !== confirmNewPasswordInput.value) {
                    passwordProfileMismatchError.classList.remove('hidden');
                    return false;
                }
            }
            passwordProfileMismatchError.classList.add('hidden');
            return true;
        }

        updateProfileForm.addEventListener('submit', function(event) {
            // Jika password baru diisi, password saat ini juga harus diisi
            if (newPasswordInput.value !== '' && currentPasswordInput.value === '') {
                // Menggunakan notifikasi toast yang sudah ada
                if (typeof showToast === 'function') {
                    showToast('Untuk mengubah password, Anda harus memasukkan Password Saat Ini.', 'error');
                } else {
                    alert('Untuk mengubah password, Anda harus memasukkan Password Saat Ini.');
                }
                event.preventDefault();
                return;
            }
            if (!validateProfilePasswords()) {
                event.preventDefault(); 
            }
        });

        if(newPasswordInput) newPasswordInput.addEventListener('input', validateProfilePasswords);
        if(confirmNewPasswordInput) confirmNewPasswordInput.addEventListener('input', validateProfilePasswords);
    }
</script>
<?php
require_once 'footer.php';
?>