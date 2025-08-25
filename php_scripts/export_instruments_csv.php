<?php
/**
 * Export Instruments to CSV
 */
declare(strict_types=1);

require_once '../config.php';

// Authorization check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    die("Akses ditolak.");
}

$conn = connectToDatabase();
if (!$conn) {
    die("Koneksi database gagal.");
}

// Ambil filter dari URL
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

// Bangun query
$sql = "SELECT i.instrument_name, i.instrument_code, it.type_name, d.department_name, i.status, i.expiry_in_days, i.notes
        FROM instruments i
        LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
        LEFT JOIN departments d ON i.department_id = d.department_id";
$whereClause = " WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $searchTerm = "%" . $searchQuery . "%";
    $whereClause .= " AND (i.instrument_name LIKE ? OR i.instrument_code LIKE ?)";
    array_push($params, $searchTerm, $searchTerm);
    $types .= "ss";
}
if (!empty($statusFilter)) { $whereClause .= " AND i.status = ?"; $params[] = $statusFilter; $types .= "s"; }
if ($typeFilter) { $whereClause .= " AND i.instrument_type_id = ?"; $params[] = $typeFilter; $types .= "i"; }
if ($departmentFilter) { $whereClause .= " AND i.department_id = ?"; $params[] = $departmentFilter; $types .= "i"; }

$sql .= $whereClause . " ORDER BY i.instrument_name ASC";

$stmt = $conn->prepare($sql);
if ($stmt && !empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate CSV
$filename = "daftar_instrumen_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF"); // BOM for UTF-8

$headers = ['Nama Instrumen', 'Kode', 'Tipe', 'Departemen', 'Status', 'Masa Kedaluwarsa (Hari)', 'Catatan'];
fputcsv($output, $headers);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$stmt->close();
$conn->close();
exit();
?>