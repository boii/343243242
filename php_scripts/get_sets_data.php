<?php
/**
 * Get Sets Data (AJAX Endpoint - Supervisor Access Corrected)
 *
 * Fetches and returns a paginated list of instrument sets as a JSON object.
 * This version now includes creator and last update information.
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

header('Content-Type: application/json');

// --- Hak akses yang disempurnakan ---
$userRole = $_SESSION['role'] ?? null;
$staffCanManageSets = (isset($app_settings['staff_can_manage_sets']) && $app_settings['staff_can_manage_sets'] === '1');

if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !(in_array($userRole, ['admin', 'supervisor']) || ($userRole === 'staff' && $staffCanManageSets))
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

// --- Konfigurasi Paginasi & Pencarian ---
$recordsPerPage = 10;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$searchQuery = trim($_GET['search'] ?? '');
$offset = ($currentPage - 1) * $recordsPerPage;

$sets = [];
$totalRecords = 0;
$totalPages = 0;
$response = ['success' => false, 'data' => [], 'pagination' => []];

$conn = connectToDatabase();
if ($conn) {
    // --- Perubahan: Menambahkan LEFT JOIN ke tabel users ---
    $baseJoins = "FROM instrument_sets s
                  LEFT JOIN users u ON s.created_by_user_id = u.user_id";
    
    $whereClause = "";
    $params = [];
    $types = "";
    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause = " WHERE (s.set_name LIKE ? OR s.set_code LIKE ?)";
        array_push($params, $searchTerm, $searchTerm);
        $types .= "ss";
    }

    // Hitung total record
    $sqlCount = "SELECT COUNT(s.set_id) as total " . $baseJoins . $whereClause;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    // Ambil data untuk halaman saat ini
    if ($totalRecords > 0) {
        // --- Perubahan: Menambahkan kolom u.full_name dan s.updated_at ke SELECT ---
        $sqlData = "SELECT s.set_id, s.set_name, s.set_code, s.updated_at, u.full_name as creator_name, COUNT(isi.instrument_id) as item_count
                    FROM instrument_sets s
                    LEFT JOIN users u ON s.created_by_user_id = u.user_id
                    LEFT JOIN instrument_set_items isi ON s.set_id = isi.set_id
                    $whereClause
                    GROUP BY s.set_id, s.set_name, s.set_code, s.updated_at, u.full_name
                    ORDER BY s.set_name ASC
                    LIMIT ? OFFSET ?";
        
        array_push($params, $recordsPerPage, $offset);
        $types .= "ii";

        if ($stmtData = $conn->prepare($sqlData)) {
            $stmtData->bind_param($types, ...$params);
            $stmtData->execute();
            $result = $stmtData->get_result();
            while ($row = $result->fetch_assoc()) {
                $sets[] = $row;
            }
            $stmtData->close();
        }
    }

    $response = [
        'success' => true,
        'data' => $sets,
        'pagination' => [
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage
        ]
    ];
    
    $conn->close();
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
exit;