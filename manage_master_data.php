<?php
/**
 * Manage Master Data Page (Tabbed Interface Revamp)
 *
 * Allows administrators and supervisors to manage core data.
 * This version is simplified by removing Method and Session management.
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

$pageTitle = "Kelola Master Data";
require_once 'header.php';

// Role-based access control: Only admins and supervisors can access this page
if (!in_array($userRole, ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'];
    header("Location: index.php");
    exit;
}

// --- Data Fetching ---
$instrumentTypes = [];
$departments = [];
$machines = [];
$packagingTypes = []; // Add new array for packaging types
$dbErrorMessage = '';

$conn = connectToDatabase();
if ($conn) {
    // Fetch instrument types
    $sqlTypes = "SELECT type_id, type_name, is_active FROM instrument_types ORDER BY type_name ASC";
    if ($resultTypes = $conn->query($sqlTypes)) {
        while ($row = $resultTypes->fetch_assoc()) $instrumentTypes[] = $row;
    } else {
        $dbErrorMessage .= " Gagal memuat data Tipe Instrumen. ";
    }

    // Fetch departments
    $sqlDepts = "SELECT department_id, department_name, is_active FROM departments ORDER BY department_name ASC";
    if ($resultDepts = $conn->query($sqlDepts)) {
        while ($row = $resultDepts->fetch_assoc()) $departments[] = $row;
    } else {
        $dbErrorMessage .= " Gagal memuat data Departemen. ";
    }

    // Fetch machines
    $sqlMachines = "SELECT machine_id, machine_name, machine_code, is_active FROM machines ORDER BY machine_name ASC";
    if ($resultMachines = $conn->query($sqlMachines)) {
        while ($row = $resultMachines->fetch_assoc()) {
            $machines[] = $row;
        }
    } else {
        $dbErrorMessage .= " Gagal memuat data Mesin. ";
    }
    
    // Fetch packaging types
    $sqlPackaging = "SELECT packaging_type_id, packaging_name, shelf_life_days, is_active FROM packaging_types ORDER BY packaging_name ASC";
    if ($resultPackaging = $conn->query($sqlPackaging)) {
        while ($row = $resultPackaging->fetch_assoc()) {
            $packagingTypes[] = $row;
        }
    } else {
        $dbErrorMessage .= " Gagal memuat data Jenis Kemasan. ";
    }

    $conn->close();
} else {
    $dbErrorMessage = "Koneksi database gagal.";
}
?>
<style>
    .tab-link { padding: 0.75rem 1.25rem; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease-in-out; color: #4b5563; font-weight: 600; }
    .tab-link:hover { background-color: #f3f4f6; color: #1f2937; }
    .tab-link.active { color: #2563eb; border-bottom-color: #2563eb; }
    .tab-content { display: none; padding-top: 1.5rem; }
    .tab-content.active { display: block; }
    .inactive-row td { color: #9ca3af; text-decoration: line-through; }
    .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
    .toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .toggle-slider { background-color: #34d399; }
    input:checked + .toggle-slider:before { transform: translateX(20px); }
    .toggle-form { display: inline-block; vertical-align: middle; }
</style>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Kelola Master Data</h2>
        <a href="index.php" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Kembali ke Dashboard
        </a>
    </div>

    <?php if ($dbErrorMessage): ?><div class="alert alert-danger"><span class="material-icons">error</span><?php echo htmlspecialchars($dbErrorMessage); ?></div><?php endif; ?>

    <div class="card">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px space-x-4" aria-label="Tabs">
                <button class="tab-link active" data-target="tab-types">Tipe Instrumen</button>
                <button class="tab-link" data-target="tab-departments">Departemen</button>
                <button class="tab-link" data-target="tab-machines">Mesin</button>
                <button class="tab-link" data-target="tab-packaging">Jenis Kemasan</button>
            </nav>
        </div>

        <div id="tab-types" class="tab-content active">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Master Tipe Instrumen</h3>
            <form action="php_scripts/master_data_add.php" method="POST" class="flex gap-2 mb-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="master_type" value="type">
                <input type="text" name="name" class="form-input flex-grow" placeholder="Nama Tipe Baru..." required>
                <button type="submit" class="btn btn-primary"><span class="material-icons">add</span></button>
            </form>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full table-auto">
                    <thead><tr class="bg-gray-100"><th class="px-4 py-2 text-left">Nama Tipe</th><th class="px-4 py-2 text-center">Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($instrumentTypes as $type): ?>
                            <tr class="<?php echo $type['is_active'] ? '' : 'inactive-row'; ?>">
                                <td class="border-t px-4 py-2"><?php echo htmlspecialchars($type['type_name']); ?></td>
                                <td class="border-t px-4 py-2 text-center">
                                    <form action="php_scripts/master_data_toggle_status.php" method="POST" class="toggle-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="master_type" value="type">
                                        <input type="hidden" name="id" value="<?php echo $type['type_id']; ?>">
                                        <label class="toggle-switch">
                                            <input type="checkbox" onchange="this.form.submit()" <?php echo $type['is_active'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-departments" class="tab-content">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Master Departemen</h3>
            <form action="php_scripts/master_data_add.php" method="POST" class="flex gap-2 mb-4">
                 <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                 <input type="hidden" name="master_type" value="department">
                 <input type="text" name="name" class="form-input flex-grow" placeholder="Nama Departemen Baru..." required>
                 <button type="submit" class="btn btn-primary"><span class="material-icons">add</span></button>
            </form>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full table-auto">
                    <thead><tr class="bg-gray-100"><th class="px-4 py-2 text-left">Nama Departemen</th><th class="px-4 py-2 text-center">Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr class="<?php echo $dept['is_active'] ? '' : 'inactive-row'; ?>">
                                <td class="border-t px-4 py-2"><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                <td class="border-t px-4 py-2 text-center">
                                    <form action="php_scripts/master_data_toggle_status.php" method="POST" class="toggle-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="master_type" value="department">
                                        <input type="hidden" name="id" value="<?php echo $dept['department_id']; ?>">
                                        <label class="toggle-switch">
                                            <input type="checkbox" onchange="this.form.submit()" <?php echo $dept['is_active'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-machines" class="tab-content">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Master Mesin Sterilisasi</h3>
            <form action="php_scripts/master_data_add.php" method="POST" class="space-y-3 mb-4 max-w-md">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="master_type" value="machine">
                <div>
                    <label for="machine_name" class="form-label">Nama Mesin</label>
                    <input type="text" id="machine_name" name="name" class="form-input" placeholder="Contoh: Autoklaf A..." required>
                </div>
                <div>
                    <label for="machine_code" class="form-label">Kode Unik Mesin</label>
                    <input type="text" id="machine_code" name="code" class="form-input" placeholder="Contoh: AA" required>
                </div>
                <button type="submit" class="btn btn-primary w-full"><span class="material-icons mr-2">add</span>Tambah Mesin</button>
            </form>
            <div class="overflow-x-auto max-h-80">
                <table class="w-full table-auto">
                    <thead><tr class="bg-gray-100"><th class="px-4 py-2 text-left">Nama Mesin</th><th class="px-4 py-2 text-left">Kode</th><th class="px-4 py-2 text-center">Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($machines as $machine): ?>
                            <tr class="<?php echo $machine['is_active'] ? '' : 'inactive-row'; ?>">
                                <td class="border-t px-4 py-2"><?php echo htmlspecialchars($machine['machine_name']); ?></td>
                                <td class="border-t px-4 py-2 font-mono"><?php echo htmlspecialchars($machine['machine_code']); ?></td>
                                <td class="border-t px-4 py-2 text-center">
                                    <form action="php_scripts/master_data_toggle_status.php" method="POST" class="toggle-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="master_type" value="machine">
                                        <input type="hidden" name="id" value="<?php echo $machine['machine_id']; ?>">
                                        <label class="toggle-switch">
                                            <input type="checkbox" onchange="this.form.submit()" <?php echo $machine['is_active'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-packaging" class="tab-content">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Master Jenis Kemasan</h3>
            <form action="php_scripts/master_data_add.php" method="POST" class="space-y-3 mb-4 max-w-md">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="master_type" value="packaging">
                <div>
                    <label for="packaging_name" class="form-label">Nama Kemasan</label>
                    <input type="text" id="packaging_name" name="name" class="form-input" placeholder="Contoh: Pouch Sterilisasi..." required>
                </div>
                <div>
                    <label for="shelf_life_days" class="form-label">Masa Kedaluwarsa (Hari)</label>
                    <input type="number" id="shelf_life_days" name="shelf_life_days" class="form-input" placeholder="Contoh: 180" required>
                </div>
                <button type="submit" class="btn btn-primary w-full"><span class="material-icons mr-2">add</span>Tambah Kemasan</button>
            </form>
            <div class="overflow-x-auto max-h-80">
                <table class="w-full table-auto">
                    <thead><tr class="bg-gray-100"><th class="px-4 py-2 text-left">Nama Kemasan</th><th class="px-4 py-2 text-left">Masa Kedaluwarsa (Hari)</th><th class="px-4 py-2 text-center">Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($packagingTypes as $packaging): ?>
                            <tr class="<?php echo $packaging['is_active'] ? '' : 'inactive-row'; ?>">
                                <td class="border-t px-4 py-2"><?php echo htmlspecialchars($packaging['packaging_name']); ?></td>
                                <td class="border-t px-4 py-2"><?php echo htmlspecialchars($packaging['shelf_life_days']); ?></td>
                                <td class="border-t px-4 py-2 text-center">
                                    <form action="php_scripts/master_data_toggle_status.php" method="POST" class="toggle-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="master_type" value="packaging">
                                        <input type="hidden" name="id" value="<?php echo $packaging['packaging_type_id']; ?>">
                                        <label class="toggle-switch">
                                            <input type="checkbox" onchange="this.form.submit()" <?php echo $packaging['is_active'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = document.getElementById(tab.dataset.target);

            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            tab.classList.add('active');
            target.classList.add('active');
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>