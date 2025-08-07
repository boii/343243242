<?php
/**
 * Export Label History to CSV (with Full Traceability)
 *
 * Fetches label records based on filter parameters and generates a CSV file
 * for download. This version includes full traceability data (load, cycle, machine)
 * and removes obsolete validation columns.
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

// --- Filter Logic & Data Fetching (mirrors label_history.php) ---
$searchQuery = trim($_GET['search_query'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$itemTypeFilter = trim($_GET['item_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$conn = connectToDatabase();
if (!$conn) {
    // Log the error and stop script execution
    error_log("CSV Export failed: Could not connect to database.");
    die("Koneksi database gagal. Tidak dapat mengekspor data.");
}

// --- Build the query with filters and new traceability joins ---
$sql = "SELECT 
            sr.label_unique_id, 
            sr.label_title, 
            sr.item_type, 
            CASE sr.item_type 
                WHEN 'instrument' THEN i.instrument_name 
                WHEN 'set' THEN s.set_name 
                ELSE 'N/A' 
            END as item_name, 
            CASE sr.item_type 
                WHEN 'instrument' THEN i.instrument_code 
                WHEN 'set' THEN s.set_code 
                ELSE NULL 
            END as item_code,
            sr.status,
            sr.created_at, 
            sr.expiry_date, 
            sr.used_at,
            creator.username as creator_username,
            sr.notes,
            sl.load_name,
            sc.cycle_number,
            sc.machine_name,
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
    // Search now includes load name and cycle number
    $whereClause .= " AND (sr.label_unique_id LIKE ? OR sr.label_title LIKE ? OR i.instrument_name LIKE ? OR s.set_name LIKE ? OR sl.load_name LIKE ? OR sc.cycle_number LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= "ssssss";
}
if (!empty($dateStart)) {
    $whereClause .= " AND DATE(sr.created_at) >= ?";
    $params[] = $dateStart;
    $types .= "s";
}
if (!empty($dateEnd)) {
    $whereClause .= " AND DATE(sr.created_at) <= ?";
    $params[] = $dateEnd;
    $types .= "s";
}
if (!empty($itemTypeFilter) && in_array($itemTypeFilter, ['instrument', 'set'])) {
    $whereClause .= " AND sr.item_type = ?";
    $params[] = $itemTypeFilter;
    $types .= "s";
}
if (!empty($statusFilter) && in_array($statusFilter, ['active', 'used', 'expired', 'pending_validation', 'recalled'])) {
    $whereClause .= " AND sr.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= $whereClause . " ORDER BY sr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    error_log("CSV Export failed: Query preparation failed: " . $conn->error);
    die("Query gagal: " . $conn->error);
}

// --- Generate CSV and trigger download ---
$filename = "riwayat_label_" . date('Y-m-d_H-i-s') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for UTF-8 compatibility with Excel
fputs($output, "\xEF\xBB\xBF");

// Define and write new headers
$headers = [
    'ID Label Unik', 'Nama Label', 'Tipe Item', 'Nama Item/Set', 'Kode Item/Set',
    'Status', 'Dibuat Pada', 'Kedaluwarsa Pada', 'Digunakan Pada',
    'Dibuat Oleh', 'Nama Muatan', 'Nomor Siklus', 'Nama Mesin', 'Tujuan Departemen', 'Catatan'
];
fputcsv($output, $headers);

// Write data rows from the database result
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['label_unique_id'],
            $row['label_title'],
            ucfirst($row['item_type']),
            $row['item_name'],
            $row['item_code'],
            ucwords(str_replace('_', ' ', $row['status'])),
            $row['created_at'],
            $row['expiry_date'],
            $row['used_at'],
            $row['creator_username'],
            $row['load_name'],
            $row['cycle_number'],
            $row['machine_name'],
            $row['destination_department_name'],
            $row['notes']
        ]);
    }
}

fclose($output);
$stmt->close();
$conn->close();

// Log the export activity
if (isset($_SESSION['user_id'])) {
    log_activity('EXPORT_LABELS', $_SESSION['user_id'], 'Mengekspor data riwayat label ke CSV.');
}

exit();