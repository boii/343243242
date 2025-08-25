<?php
/**
 * Export Label History to Excel (HTML Table)
 *
 * Fetches label records based on filter parameters and generates an HTML table
 * masquerading as an Excel file for download. This provides basic formatting.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

// Require config for DB connection and session start
require_once '../config.php';

// Authorization check: Only logged-in users can export
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    die("Akses ditolak. Anda harus login untuk mengekspor data.");
}

// --- Filter Logic & Data Fetching (mirrors export_labels_csv.php) ---
$searchQuery = trim($_GET['search_query'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$itemTypeFilter = trim($_GET['item_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$conn = connectToDatabase();
if (!$conn) {
    error_log("Excel Export failed: Could not connect to database.");
    die("Koneksi database gagal. Tidak dapat mengekspor data.");
}

// --- Build the query with filters ---
$sql = "SELECT 
            sr.label_unique_id, sr.label_title, sr.item_type, 
            CASE sr.item_type WHEN 'instrument' THEN i.instrument_name WHEN 'set' THEN s.set_name ELSE 'N/A' END as item_name, 
            CASE sr.item_type WHEN 'instrument' THEN i.instrument_code WHEN 'set' THEN s.set_code ELSE NULL END as item_code,
            sr.status, sr.created_at, sr.expiry_date, sr.used_at,
            creator.username as creator_username,
            sr.notes, sl.load_name, sc.cycle_number, sc.machine_name,
            d.department_name as destination_department_name
        FROM sterilization_records sr
        LEFT JOIN users creator ON sr.created_by_user_id = creator.user_id
        LEFT JOIN instruments i ON sr.item_type = 'instrument' AND sr.item_id = i.instrument_id
        LEFT JOIN instrument_sets s ON sr.item_type = 'set' AND sr.item_id = s.set_id
        LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
        LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
        LEFT JOIN departments d ON sl.destination_department_id = d.department_id";

$whereClause = " WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $searchTerm = "%" . $searchQuery . "%";
    $whereClause .= " AND (sr.label_unique_id LIKE ? OR sr.label_title LIKE ? OR i.instrument_name LIKE ? OR s.set_name LIKE ? OR sl.load_name LIKE ? OR sc.cycle_number LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= "ssssss";
}
if (!empty($dateStart)) { $whereClause .= " AND DATE(sr.created_at) >= ?"; $params[] = $dateStart; $types .= "s"; }
if (!empty($dateEnd)) { $whereClause .= " AND DATE(sr.created_at) <= ?"; $params[] = $dateEnd; $types .= "s"; }
if (!empty($itemTypeFilter) && in_array($itemTypeFilter, ['instrument', 'set'])) { $whereClause .= " AND sr.item_type = ?"; $params[] = $itemTypeFilter; $types .= "s"; }
if (!empty($statusFilter) && in_array($statusFilter, ['active', 'used', 'expired', 'pending_validation', 'recalled'])) { $whereClause .= " AND sr.status = ?"; $params[] = $statusFilter; $types .= "s"; }

$sql .= $whereClause . " ORDER BY sr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    error_log("Excel Export failed: Query preparation failed: " . $conn->error);
    die("Query gagal: " . $conn->error);
}

// --- Generate Excel (HTML) and trigger download ---
$filename = "riwayat_label_" . date('Y-m-d_H-i-s') . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Define headers
$headers = [
    'ID Label Unik', 'Nama Label', 'Tipe Item', 'Nama Item/Set', 'Kode Item/Set',
    'Status', 'Dibuat Pada', 'Kedaluwarsa Pada', 'Digunakan Pada',
    'Dibuat Oleh', 'Nama Muatan', 'Nomor Siklus', 'Nama Mesin', 'Tujuan Departemen', 'Catatan'
];

// Start HTML output
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>';
echo '<table border="1">';
echo '<thead><tr>';
foreach ($headers as $header) {
    echo '<th>' . htmlspecialchars($header) . '</th>';
}
echo '</tr></thead>';
echo '<tbody>';

// Write data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['label_unique_id'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['label_title'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst($row['item_type'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars($row['item_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['item_code'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $row['status'] ?? ''))) . '</td>';
        echo '<td>' . htmlspecialchars($row['created_at'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['expiry_date'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['used_at'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['creator_username'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['load_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['cycle_number'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['machine_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['destination_department_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['notes'] ?? '') . '</td>';
        echo '</tr>';
    }
}

echo '</tbody></table>';
echo '</body></html>';

$stmt->close();
$conn->close();

exit();
?>