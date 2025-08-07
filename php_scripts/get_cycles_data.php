<?php
/**
 * Get Sterilization Cycles Data (AJAX Endpoint - Simplified)
 *
 * This version is corrected to remove filtering by sterilization_method,
 * aligning with the simplified database schema.
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

// Authorization check for admin, supervisor, or authorized staff roles
$userRole = $_SESSION['role'] ?? null;
$staffCanValidateCycles = (isset($app_settings['staff_can_validate_cycles']) && $app_settings['staff_can_validate_cycles'] === '1');

if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanValidateCycles))
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}


// --- Pagination & Filter ---
$recordsPerPage = 15;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($currentPage - 1) * $recordsPerPage;

// --- Filter parameters ---
$searchQuery = trim($_GET['search_query'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$machineFilter = filter_input(INPUT_GET, 'machine_id', FILTER_VALIDATE_INT);
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
// PERUBAHAN: Filter metode dihapus

$response = ['success' => false, 'data' => [], 'pagination' => []];

$conn = connectToDatabase();
if ($conn) {
    // --- Build query with all filters ---
    $baseSql = "FROM sterilization_cycles sc LEFT JOIN users u ON sc.operator_user_id = u.user_id LEFT JOIN machines m ON sc.machine_name = m.machine_name";
    $whereClause = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause .= " AND (sc.cycle_number LIKE ? OR sc.machine_name LIKE ? OR u.full_name LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sss";
    }
    if (!empty($statusFilter)) {
        $whereClause .= " AND sc.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    if ($machineFilter) {
        $whereClause .= " AND m.machine_id = ?";
        $params[] = $machineFilter;
        $types .= "i";
    }
    if (!empty($dateStart)) {
        $whereClause .= " AND DATE(sc.cycle_date) >= ?";
        $params[] = $dateStart;
        $types .= "s";
    }
    if (!empty($dateEnd)) {
        $whereClause .= " AND DATE(sc.cycle_date) <= ?";
        $params[] = $dateEnd;
        $types .= "s";
    }
    // PERUBAHAN: Kondisi filter metode dihapus dari sini

    // Count total records
    $sqlCount = "SELECT COUNT(sc.cycle_id) as total " . $baseSql . $whereClause;
    $totalRecords = 0;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    // Fetch data for the current page
    $cycles = [];
    if ($totalRecords > 0) {
        // PERUBAHAN: Menghapus sc.sterilization_method dari SELECT
        $sqlData = "SELECT sc.cycle_id, sc.cycle_number, sc.machine_name, sc.cycle_date, sc.status, u.full_name as operator_name
                    " . $baseSql . $whereClause . "
                    ORDER BY CASE sc.status 
                                WHEN 'menunggu_validasi' THEN 1
                                ELSE 2
                             END, sc.cycle_date DESC
                    LIMIT ? OFFSET ?";
        
        array_push($params, $recordsPerPage, $offset);
        $types .= "ii";

        if ($stmtData = $conn->prepare($sqlData)) {
            $stmtData->bind_param($types, ...$params);
            $stmtData->execute();
            $result = $stmtData->get_result();
            while ($row = $result->fetch_assoc()) {
                $statusInfo = getUniversalStatusBadge($row['status']);
                $row['status_text'] = $statusInfo['text'];
                $row['status_class'] = $statusInfo['class'];
                $cycles[] = $row;
            }
            $stmtData->close();
        }
    }

    $response = [
        'success' => true,
        'data' => $cycles,
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