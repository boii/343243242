<?php
/**
 * Export Sterilization Loads to CSV (with Date Filtering)
 *
 * Fetches sterilization load records based on all filter parameters,
 * including a date range, and generates a CSV file for download.
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

require_once '../config.php';

// Authorization check: Only logged-in users can export
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    die("Akses ditolak. Anda harus login untuk mengekspor data.");
}

// --- Filter Logic & Data Fetching (mirrors get_loads_data.php) ---
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$machineFilter = filter_input(INPUT_GET, 'machine_id', FILTER_VALIDATE_INT);
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
// --- PERUBAHAN: Menangkap parameter tanggal ---
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');


$conn = connectToDatabase();
if (!$conn) {
    error_log("CSV Export (Loads) failed: Could not connect to database.");
    die("Koneksi database gagal. Tidak dapat mengekspor data.");
}

// Build the query based on filters
$baseJoins = "FROM sterilization_loads sl
              LEFT JOIN users u ON sl.created_by_user_id = u.user_id
              LEFT JOIN machines m ON sl.machine_id = m.machine_id
              LEFT JOIN departments d ON sl.destination_department_id = d.department_id
              LEFT JOIN sterilization_load_items sli ON sl.load_id = sli.load_id";

$whereClause = " WHERE 1=1";
$params = [];
$types = "";
if (!empty($searchQuery)) {
    $searchTerm = "%" . $searchQuery . "%";
    $whereClause .= " AND (sl.load_name LIKE ? OR m.machine_name LIKE ? OR d.department_name LIKE ? OR u.full_name LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= "ssss";
}
if (!empty($statusFilter)) {
    $whereClause .= " AND sl.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
if ($machineFilter) {
    $whereClause .= " AND sl.machine_id = ?";
    $params[] = $machineFilter;
    $types .= "i";
}
if ($departmentFilter) {
    $whereClause .= " AND sl.destination_department_id = ?";
    $params[] = $departmentFilter;
    $types .= "i";
}
// --- PERUBAHAN: Menambahkan logika filter tanggal ke query ---
if (!empty($dateStart)) {
    $whereClause .= " AND DATE(sl.created_at) >= ?";
    $params[] = $dateStart;
    $types .= "s";
}
if (!empty($dateEnd)) {
    $whereClause .= " AND DATE(sl.created_at) <= ?";
    $params[] = $dateEnd;
    $types .= "s";
}
// --- AKHIR PERUBAHAN ---

$sql = "SELECT 
            sl.load_name, 
            sl.status, 
            sl.created_at, 
            u.full_name as creator_name,
            m.machine_name,
            d.department_name as destination_department_name,
            COUNT(sli.load_item_id) as item_count,
            sc.cycle_number
        {$baseJoins}
        LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
        {$whereClause}
        GROUP BY sl.load_id
        ORDER BY sl.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    error_log("CSV Export (Loads) failed: Query preparation failed: " . $conn->error);
    die("Query gagal: " . $conn->error);
}

// --- Generate CSV and trigger download ---
$filename = "laporan_muatan_" . date('Y-m-d_H-i-s') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for UTF-8 compatibility with Excel
fputs($output, "\xEF\xBB\xBF");

// Define and write headers
$headers = [
    'Nama Muatan', 'Mesin', 'Tujuan', 'Status', 'Nomor Siklus', 'Jumlah Item', 'Dibuat Pada', 'Dibuat Oleh'
];
fputcsv($output, $headers);

// Write data rows from the database result
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['load_name'],
            $row['machine_name'],
            $row['destination_department_name'] ?? 'Stok Umum',
            getUniversalStatusBadge($row['status'])['text'], // Menggunakan helper function
            $row['cycle_number'] ?? '-',
            $row['item_count'],
            $row['created_at'],
            $row['creator_name']
        ]);
    }
}

fclose($output);
$stmt->close();
$conn->close();

// Log the export activity
if (isset($_SESSION['user_id'])) {
    log_activity('EXPORT_LOADS', $_SESSION['user_id'], 'Mengekspor data riwayat muatan ke CSV.');
}

exit();