<?php
/**
 * Print Queue Page (with Breadcrumbs)
 *
 * Displays a list of unprinted labels from a specific load, allowing
 * the user to select which ones to print in a batch.
 * This version includes breadcrumb navigation for better context.
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

$pageTitle = "Antrean Cetak";
require_once 'header.php'; // Includes session check, CSRF token, etc.

$loadId = filter_input(INPUT_GET, 'load_id', FILTER_VALIDATE_INT);

if (!$loadId) {
    $_SESSION['error_message'] = "ID Muatan tidak valid untuk menampilkan antrean cetak.";
    header("Location: manage_loads.php");
    exit;
}

// Fetch load name for display
$loadName = "Muatan ID: $loadId";
$conn_pq = connectToDatabase();
if ($conn_pq) {
    $stmt = $conn_pq->prepare("SELECT load_name FROM sterilization_loads WHERE load_id = ?");
    $stmt->bind_param("i", $loadId);
    $stmt->execute();
    if ($result = $stmt->get_result()->fetch_assoc()) {
        $loadName = $result['load_name'];
    }
    $stmt->close();
    $conn_pq->close();
}

// Panggil fungsi breadcrumb. Karena ini adalah halaman terakhir dalam hierarki,
// kita tidak perlu memberikan judul dinamis, nama dari sitemap akan digunakan.
render_breadcrumbs();
?>
<style>
    .table-fixed-layout { table-layout: fixed; }
    .col-check { width: 5%; }
    .col-name { width: 55%; }
    .col-id { width: 40%; }
</style>

<main class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-700">Antrean Cetak</h2>
            <p class="text-sm text-gray-500">Untuk Muatan: <strong><?php echo htmlspecialchars($loadName); ?></strong></p>
        </div>
        <a href="load_detail.php?load_id=<?php echo $loadId; ?>" class="btn btn-secondary">
            <span class="material-icons mr-2">arrow_back</span>Kembali ke Detail Muatan
        </a>
    </div>

    <form id="printQueueForm" action="print_multiple_router.php" method="POST" target="_blank">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="load_id" value="<?php echo $loadId; ?>">
        
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-700">Pilih Label untuk Dicetak</h3>
                <div class="flex items-center">
                    <input type="checkbox" id="selectAllCheckbox" class="h-4 w-4 mr-2">
                    <label for="selectAllCheckbox">Pilih Semua</label>
                </div>
            </div>
            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full table-auto table-fixed-layout">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-sm">
                        <tr>
                            <th class="py-2 px-4 text-center col-check"></th>
                            <th class="py-2 px-4 text-left col-name">Nama Item</th>
                            <th class="py-2 px-4 text-left col-id">ID Label Unik</th>
                        </tr>
                    </thead>
                    <tbody id="printQueueTableBody" class="text-gray-600 text-sm">
                        </tbody>
                </table>
            </div>
            <div class="text-right mt-6">
                <button type="submit" id="printSelectedBtn" class="btn btn-primary" disabled>
                    <span class="material-icons mr-2">print</span>Cetak Label Terpilih (0)
                </button>
            </div>
        </div>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loadId = <?php echo $loadId; ?>;
    const tableBody = document.getElementById('printQueueTableBody');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const printSelectedBtn = document.getElementById('printSelectedBtn');
    const printQueueForm = document.getElementById('printQueueForm');

    function fetchPrintQueue() {
        tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-4">Memuat antrean cetak...</td></tr>';
        
        fetch(`php_scripts/get_print_queue_data.php?load_id=${loadId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTable(data.labels);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="3" class="text-center py-4 text-red-500">Error: ${data.error || 'Gagal memuat data.'}</td></tr>`;
                }
            });
    }

    function renderTable(labels) {
        tableBody.innerHTML = '';
        if (labels.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="3" class="text-center py-6 text-gray-500">Tidak ada label dalam antrean untuk muatan ini. Mungkin semua sudah dicetak.</td></tr>`;
            return;
        }
        
        labels.forEach(label => {
            const row = `
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3 px-4 text-center">
                        <input type="checkbox" name="record_ids[]" value="${label.record_id}" class="label-checkbox h-5 w-5">
                    </td>
                    <td class="py-3 px-4 truncate">${escapeHtml(label.label_title)}</td>
                    <td class="py-3 px-4 font-mono">${escapeHtml(label.label_unique_id)}</td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });
    }

    function updatePrintButtonState() {
        const checkedCount = document.querySelectorAll('.label-checkbox:checked').length;
        if (checkedCount > 0) {
            printSelectedBtn.disabled = false;
            printSelectedBtn.innerHTML = `<span class="material-icons mr-2">print</span>Cetak Label Terpilih (${checkedCount})`;
        } else {
            printSelectedBtn.disabled = true;
            printSelectedBtn.innerHTML = `<span class="material-icons mr-2">print</span>Cetak Label Terpilih (0)`;
        }
    }

    selectAllCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.label-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updatePrintButtonState();
    });

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('label-checkbox')) {
            updatePrintButtonState();
        }
    });
    
    printQueueForm.addEventListener('submit', function() {
        // Tandai sebagai sudah dicetak setelah form disubmit
        setTimeout(() => {
            const formData = new FormData(printQueueForm);
             fetch('php_scripts/process_print_jobs.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                // Refresh queue to show only remaining items
                fetchPrintQueue();
                selectAllCheckbox.checked = false;
                updatePrintButtonState();
            });
        }, 1000); // Delay to allow the print page to open
    });

    function escapeHtml(str) { 
        return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&lt;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); 
    }

    // Initial fetch
    fetchPrintQueue();
});
</script>

<?php
require_once 'footer.php';
?>