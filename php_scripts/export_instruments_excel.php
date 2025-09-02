<?php
/**
 * Export Instruments to Excel (Robust Version)
 */
declare(strict_types=1);

require_once '../config.php';

// Authorization, DB connection, and data fetching logic remains the same
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { http_response_code(403); die("Akses ditolak."); }
$conn = connectToDatabase();
if (!$conn) { die("Koneksi database gagal."); }

$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);

$sql = "SELECT i.instrument_name, i.instrument_code, it.type_name, d.department_name, i.status, i.expiry_in_days, i.notes
        FROM instruments i
        LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
        LEFT JOIN departments d ON i.department_id = d.department_id";
$whereClause = " WHERE 1=1";
$params = [];
$types = "";

if (!empty($searchQuery)) { $searchTerm = "%" . $searchQuery . "%"; $whereClause .= " AND (i.instrument_name LIKE ? OR i.instrument_code LIKE ?)"; array_push($params, $searchTerm, $searchTerm); $types .= "ss"; }
if (!empty($statusFilter)) { $whereClause .= " AND i.status = ?"; $params[] = $statusFilter; $types .= "s"; }
if ($typeFilter) { $whereClause .= " AND i.instrument_type_id = ?"; $params[] = $typeFilter; $types .= "i"; }
if ($departmentFilter) { $whereClause .= " AND i.department_id = ?"; $params[] = $departmentFilter; $types .= "i"; }

$sql .= $whereClause . " ORDER BY i.instrument_name ASC";
$stmt = $conn->prepare($sql);
if ($stmt && !empty($types)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// Generate Excel (HTML Table)
$filename = "daftar_instrumen_" . date('Y-m-d') . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>';
echo '<table border="1">';
$headers = ['Nama Instrumen', 'Kode', 'Tipe', 'Departemen', 'Status', 'Masa Kedaluwarsa (Hari)', 'Catatan'];
echo '<thead><tr>';
foreach ($headers as $header) {
    echo '<th>' . htmlspecialchars($header) . '</th>';
}
echo '</tr></thead><tbody>';

// --- PERBAIKAN UTAMA DI SINI ---
// Menangani setiap kolom secara eksplisit untuk memastikan tidak ada nilai NULL yang lolos.
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['instrument_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['instrument_code'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['type_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['department_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['status'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars((string)($row['expiry_in_days'] ?? '')) . '</td>'; // Cast ke string untuk keamanan
    echo '<td>' . htmlspecialchars($row['notes'] ?? '') . '</td>';
    echo '</tr>';
}
// --- AKHIR PERBAIKAN ---

echo '</tbody></table></body></html>';

$stmt->close();
$conn->close();
exit();
?>