<?php
/**
 * Get Activity Log Data (AJAX Endpoint)
 *
 * Fetches and returns a paginated list of activity logs as a JSON object.
 * Supports extensive filtering. Called from activity_log.php.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   Gemini
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once '../config.php';

header('Content-Type: application/json');

// --- PERUBAHAN DIMULAI: Memperbarui hak akses untuk Supervisor dan Staff ---
$userRole = $_SESSION['role'] ?? null;
$staffCanViewActivityLog = (isset($app_settings['staff_can_view_activity_log']) && $app_settings['staff_can_view_activity_log'] === '1');

// Authorization check: Admins, Supervisors, OR Staff with specific permission can access
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !($userRole === 'admin' || $userRole === 'supervisor' || ($userRole === 'staff' && $staffCanViewActivityLog))
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}
// --- PERUBAHAN SELESAI ---

// --- Pagination & Filter Configuration ---
$recordsPerPage = 25;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($currentPage - 1) * $recordsPerPage;

$searchQuery = trim($_GET['search_query'] ?? '');
$filterUser = trim($_GET['filter_user'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');

$logs = [];
$totalRecords = 0;
$totalPages = 0;
$response = ['success' => false, 'data' => [], 'pagination' => []];

$conn = connectToDatabase();
if ($conn) {
    $baseSql = "FROM activity_log al LEFT JOIN users u ON al.user_id = u.user_id";
    $whereClause = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause .= " AND (al.action_type LIKE ? OR al.details LIKE ? OR al.ip_address LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sss";
    }
    if (!empty($filterUser)) {
        $whereClause .= " AND al.user_id = ?";
        $params[] = $filterUser;
        $types .= "i";
    }
    if (!empty($dateStart)) {
        $whereClause .= " AND DATE(al.log_timestamp) >= ?";
        $params[] = $dateStart;
        $types .= "s";
    }
    if (!empty($dateEnd)) {
        $whereClause .= " AND DATE(al.log_timestamp) <= ?";
        $params[] = $dateEnd;
        $types .= "s";
    }
    
    // Count total records
    $sqlCount = "SELECT COUNT(al.log_id) as total " . $baseSql . $whereClause;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    // Fetch data for the current page
    if ($totalRecords > 0) {
        $sqlData = "SELECT al.*, u.username, u.full_name " . $baseSql . $whereClause . " ORDER BY al.log_timestamp DESC LIMIT ? OFFSET ?";
        array_push($params, $recordsPerPage, $offset);
        $types .= "ii";

        if ($stmtData = $conn->prepare($sqlData)) {
            $stmtData->bind_param($types, ...$params);
            $stmtData->execute();
            $result = $stmtData->get_result();
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            $stmtData->close();
        }
    }

    $response = [
        'success' => true,
        'data' => $logs,
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