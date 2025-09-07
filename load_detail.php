<?php
/**
 * Sterilization Load Detail Page (Final, Feature-Complete & Polished Version)
 *
 * This version uses an external JavaScript file for better modularity
 * and to ensure all library functions are available.
 * It now includes an elegant Item Picker modal as an alternative to search.
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

$pageTitle = "Detail Muatan Sterilisasi";
require_once 'header.php';

$loadId = filter_input(INPUT_GET, 'load_id', FILTER_VALIDATE_INT);

if (!$loadId) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'ID Muatan tidak valid.'];
    header("Location: manage_loads.php");
    exit;
}

// --- Menyiapkan data untuk dikirim ke JavaScript ---
$allInstrumentsGrouped = [];
$allInstrumentsFlat = [];
$allMasterSetsInfo = [];
$activeMachines = [];
$activeDepartments = [];
$allMasterSetsData = [];
$dbErrorMessage = '';

$conn = connectToDatabase();
if ($conn) {
    // Fetch all instruments for the set editor
    $sqlAllInstruments = "SELECT i.instrument_id, i.instrument_name, i.instrument_code, it.type_name
                          FROM instruments i
                          LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
                          ORDER BY it.type_name, i.instrument_name ASC";
    if ($resultInstruments = $conn->query($sqlAllInstruments)) {
        while ($instrument = $resultInstruments->fetch_assoc()) {
            $allInstrumentsFlat[] = $instrument;
            $typeName = $instrument['type_name'] ?? 'Lain-lain';
            $allInstrumentsGrouped[$typeName][] = $instrument;
        }
    } else {
        $dbErrorMessage .= ' Gagal memuat daftar instrumen. ';
    }

    // Mengambil informasi dasar semua master set untuk Item Picker
    $sqlAllSets = "SELECT set_id, set_name, set_code FROM instrument_sets ORDER BY set_name ASC";
    if($resultAllSets = $conn->query($sqlAllSets)) {
        while($set = $resultAllSets->fetch_assoc()) {
            $allMasterSetsInfo[] = $set;
        }
    } else {
        $dbErrorMessage .= ' Gagal memuat daftar master set. ';
    }


    // Fetch master set definitions (untuk perbandingan kustomisasi)
    $sqlMasterSets = "SELECT set_id, instrument_id, quantity FROM instrument_set_items";
    if ($resultMasterSets = $conn->query($sqlMasterSets)) {
        while ($item = $resultMasterSets->fetch_assoc()) {
            $allMasterSetsData[$item['set_id']][] = ['instrument_id' => (int)$item['instrument_id'], 'quantity' => (int)$item['quantity']];
        }
    } else {
        $dbErrorMessage .= ' Gagal memuat definisi master set. ';
    }

    // Fetch active machines for the edit modal
    $sqlMachines = "SELECT machine_id, machine_name FROM machines WHERE is_active = 1 UNION (SELECT m.machine_id, m.machine_name FROM machines m JOIN sterilization_loads sl ON m.machine_id = sl.machine_id WHERE sl.load_id = ?)";
    $stmtMachines = $conn->prepare($sqlMachines);
    $stmtMachines->bind_param("i", $loadId);
    $stmtMachines->execute();
    if ($result = $stmtMachines->get_result()) while($row = $result->fetch_assoc()) $activeMachines[] = $row;
    $stmtMachines->close();

    // Fetch active departments for the edit modal
    $sqlDepts = "SELECT department_id, department_name FROM departments WHERE is_active = 1 UNION (SELECT d.department_id, d.department_name FROM departments d JOIN sterilization_loads sl ON d.department_id = sl.destination_department_id WHERE sl.load_id = ?)";
    $stmtDepts = $conn->prepare($sqlDepts);
    $stmtDepts->bind_param("i", $loadId);
    $stmtDepts->execute();
    if ($result = $stmtDepts->get_result()) while($row = $result->fetch_assoc()) $activeDepartments[] = $row;
    $stmtDepts->close();

    $conn->close();
} else {
    $dbErrorMessage = "Koneksi database gagal.";
}

// Mendefinisikan file JS spesifik halaman untuk dimuat oleh footer
$page_specific_js = 'assets/js/load_detail.js';
?>
<style>
    .set-contents-details { background-color: #f9fafb; display: none; }
    .set-contents-details td { padding-top: 0.5rem; padding-bottom: 0.5rem; border-top: 1px solid #e5e7eb; }
    .instrument-list-in-set { list-style-type: disc; list-style-position: inside; padding-left: 1rem; }
    .set-editor-panel { display: none; margin-top: 1.5rem; padding: 1.5rem; border: 2px dashed #93c5fd; background-color: #f0f9ff; border-radius: 0.5rem; }
    .detail-grid-improved dt { display: flex; align-items: center; gap: 0.5rem; }
    .detail-grid-improved dt .material-icons { font-size: 1rem; color: #6b7280; }
    .customized-set-icon { font-size: 1rem; color: #4f46e5; vertical-align: middle; margin-left: 0.25rem; }
    #itemSearchResults .search-result-item { display: flex; align-items: center; padding: 0.5rem 0.75rem; cursor: pointer; }
    #itemSearchResults .search-result-item:hover { background-color: #f3f4f6; }
</style>
<main class="container mx-auto px-6 py-8" id="main-content-container">
    <div id="loading-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" style="display: none;">
        <div class="text-white text-xl">Memproses...</div>
    </div>

    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <div class="flex items-center gap-4">
            <h2 class="text-2xl font-semibold text-gray-700">Detail Muatan: <span id="loadNameDisplay">Memuat...</span></h2>
            <div id="statusBadgeContainer"></div>
        </div>
        <a href="manage_loads.php" class="btn btn-secondary"><span class="material-icons mr-2">arrow_back</span>Kembali ke Daftar Muatan</a>
    </div>

    <div id="ajax-message-container"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <div class="card">
                <div id="infoCardHeader" class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Informasi Muatan</h3>
                    <button id="openEditModalBtn" class="btn-icon btn-icon-edit" title="Edit Detail Muatan" style="display: none;"><span class="material-icons">edit</span></button>
                </div>
                <dl id="loadInfoPanel" class="detail-grid detail-grid-improved text-sm"></dl>
            </div>
            <div id="actionContainer" class="card"></div>
        </div>

        <div class="lg:col-span-2 card">
            <div id="addItemContainer" style="display: none;">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Tambah Item ke Muatan</h3>
                <div class="flex gap-2">
                    <div class="relative flex-grow">
                        <input type="text" id="itemSearchInput" class="form-input" placeholder="Ketik nama atau kode Set/Instrumen...">
                        <div id="itemSearchResults" class="absolute bg-white border border-gray-300 w-full max-h-60 overflow-y-auto z-10 mt-1 hidden rounded-md shadow-lg"></div>
                    </div>
                    <button id="openItemPickerBtn" type="button" class="btn btn-secondary whitespace-nowrap"><span class="material-icons mr-2">view_list</span>Lihat Semua</button>
                </div>
                 <hr class="my-6">
            </div>

            <h3 class="text-xl font-semibold text-gray-800 mb-4">Isi Muatan (<span id="itemCount">0</span>)</h3>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead><tr class="bg-gray-100 text-gray-600 uppercase text-sm"><th class="py-2 px-4 text-left">Nama Item</th><th class="py-2 px-4 text-left">Tipe</th><th class="py-2 px-4 text-center">Aksi</th></tr></thead>
                    <tbody id="loadItemsTableBody"></tbody>
                </table>
            </div>

            <div id="setEditorPanel" class="set-editor-panel">
                <h3 class="text-xl font-semibold text-gray-700 mb-1">Editor Isi Set untuk Muatan Ini</h3>
                <p class="text-sm text-gray-600 mb-4">Anda sedang mengedit: <strong id="editingSetName" class="text-blue-600"></strong>. Perubahan hanya berlaku untuk item ini di dalam muatan ini.</p>
                <input type="hidden" id="editingLoadItemId">
                <input type="text" id="editorInstrumentSearch" class="form-input mb-2" placeholder="Cari instrumen untuk ditambah/dihapus...">
                <div id="instrumentPickerContainer" class="max-h-60 overflow-y-auto border rounded-md bg-white">
                    <table class="instrument-picker-table">
                        <thead><tr><th class="col-checkbox"><span class="material-icons text-sm">checklist</span></th><th>Nama Instrumen</th><th class="col-quantity">Kuantitas</th></tr></thead>
                        <tbody id="instrumentPickerTbody">
                            <?php if (!empty($allInstrumentsGrouped)): ?>
                                <?php foreach ($allInstrumentsGrouped as $typeName => $instruments): ?>
                                    <tr class="group-header"><td colspan="3"><?php echo htmlspecialchars($typeName); ?></td></tr>
                                    <?php foreach ($instruments as $instrument): ?>
                                        <tr class="instrument-row" data-search-term="<?php echo htmlspecialchars(strtolower($instrument['instrument_name'].' '.$instrument['instrument_code'])); ?>">
                                            <td class="col-checkbox"><input type="checkbox" data-instrument-id="<?php echo $instrument['instrument_id']; ?>" class="instrument-checkbox"></td>
                                            <td><?php echo htmlspecialchars($instrument['instrument_name']); ?></td>
                                            <td class="col-quantity"><input type="number" data-instrument-id="<?php echo $instrument['instrument_id']; ?>" class="form-input form-input-sm w-20 text-center" min="1" value="1" style="display: none;"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center p-4">Tidak ada instrumen tersedia.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-4 space-x-2"><button type="button" id="cancelSetEditBtn" class="btn btn-secondary">Batal</button><button type="button" id="saveSetChangesBtn" class="btn btn-primary">Simpan Perubahan Set</button></div>
            </div>
        </div>
    </div>
</main>

<div id="itemPickerModal" class="item-picker-modal-overlay">
    <div class="item-picker-modal-container">
        <div class="item-picker-modal-header">
            <h3 class="text-lg font-semibold text-gray-800">Pilih Item dari Daftar</h3>
            <button id="closeItemPickerBtn" class="btn-icon btn-icon-action"><span class="material-icons">close</span></button>
        </div>
        <div class="item-picker-modal-content">
            <div id="picker-categories" class="item-picker-sidebar">
                </div>
            <div class="item-picker-main">
                <div class="picker-main-header">
                    <input type="text" id="pickerSearchInput" class="form-input" placeholder="Cari di dalam kategori...">
                    <label class="flex items-center text-sm ml-4">
                        <input type="checkbox" id="pickerSelectAll" class="h-4 w-4 mr-2">
                        Pilih Semua
                    </label>
                </div>
                <div id="picker-items-container" class="picker-items-list">
                    </div>
            </div>
        </div>
        <div class="item-picker-modal-footer">
            <span id="pickerSelectedItemCount" class="text-sm font-medium text-gray-600">0 item terpilih</span>
            <button id="addSelectedItemsBtn" class="btn btn-primary" disabled>
                <span class="material-icons mr-2">add</span>Tambah Item Terpilih
            </button>
        </div>
    </div>
</div>


<div id="processLoadModal" class="modal-overlay">
    <div class="modal-content text-left">
        <h3 class="text-lg font-bold mb-4 text-center">Proses Muatan</h3>
        <p class="text-sm text-gray-600 mb-4">Pilih bagaimana Anda ingin memproses muatan ini. Anda dapat membuat siklus baru atau menggabungkannya dengan siklus lain dari mesin yang sama yang telah selesai hari ini.</p>
        <form id="processLoadForm" action="php_scripts/load_process_cycle.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="load_id" value="<?php echo $loadId; ?>">
            <div class="space-y-3">
                <label class="block p-3 border rounded-lg has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400"><input type="radio" name="process_type" value="create_new" class="mr-2" checked> Buat Siklus Baru</label>
                <label class="block p-3 border rounded-lg has-[:checked]:bg-blue-50 has-[:checked]:border-blue-400"><input type="radio" name="process_type" value="merge_existing" class="mr-2"> Gabungkan ke Siklus yang Ada</label>
            </div>

            <div id="mergeCycleDropdownContainer" class="hidden mt-4">
                <label for="target_cycle_id" class="form-label">Pilih Siklus Target</label>
                <select id="target_cycle_id" name="target_cycle_id" class="form-select" disabled><option>Memuat siklus...</option></select>
            </div>
            <div class="flex justify-center gap-4 mt-6">
                <button type="button" id="cancelProcessBtn" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Lanjutkan & Proses</button>
            </div>
        </form>
    </div>
</div>

<div id="editLoadModal" class="modal-overlay">
    <div class="modal-content text-left max-w-lg">
        <h3 class="text-lg font-bold mb-6 text-center">Edit Detail Muatan</h3>
        <form id="editLoadForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="load_id" id="editLoadId">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-2">
                    <label for="edit_machine_id" class="form-label md:text-right md:col-span-1">Mesin</label>
                    <div class="md:col-span-3"><select id="edit_machine_id" name="machine_id" class="form-select" required><?php foreach($activeMachines as $machine): ?><option value="<?php echo $machine['machine_id']; ?>"><?php echo htmlspecialchars($machine['machine_name']); ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-2">
                    <label for="edit_destination_department_id" class="form-label md:text-right md:col-span-1">Tujuan</label>
                    <div class="md:col-span-3"><select id="edit_destination_department_id" name="destination_department_id" class="form-select"><option value="">-- Stok Umum --</option><?php foreach($activeDepartments as $dept): ?><option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option><?php endforeach; ?></select></div>
                </div>
                 <div class="grid grid-cols-1 md:grid-cols-4 items-center gap-2">
                    <label for="edit_notes" class="form-label md:text-right md:col-span-1">Catatan</label>
                    <div class="md:col-span-3"><input type="text" id="edit_notes" name="notes" class="form-input"></div>
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-8"><button type="button" id="cancelEditBtn" class="btn btn-secondary">Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div>
        </form>
    </div>
</div>

<div id="removeItemModal" class="modal-overlay">
    <div class="modal-content">
        <h3 class="text-lg font-bold mb-2">Konfirmasi Penghapusan</h3>
        <p class="mb-4">Apakah Anda yakin ingin menghapus item ini dari muatan?</p>
        <div class="flex justify-center gap-4">
            <button type="button" id="cancelRemoveBtn" class="btn btn-secondary">Batal</button>
            <button type="button" id="confirmRemoveBtn" class="btn bg-red-600 text-white hover:bg-red-700">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
    const pageData = {
        loadId: <?php echo json_encode($loadId); ?>,
        csrfToken: <?php echo json_encode($csrfToken); ?>,
        allInstrumentsData: <?php echo json_encode($allInstrumentsFlat); ?>,
        allMasterSetsInfo: <?php echo json_encode($allMasterSetsInfo); ?>,
        allMasterSetsData: <?php echo json_encode($allMasterSetsData); ?>,
        allInstrumentsGrouped: <?php echo json_encode($allInstrumentsGrouped); ?>
    };
</script>

<?php
require_once 'footer.php';
?>
