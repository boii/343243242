<?php
/**
 * Get Instruments Data (AJAX - Final Version)
 *
 * This version fetches all necessary data including notes for a rich frontend display.
 * It also grants access to supervisors and authorized staff.
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

// --- Authorization Check for Admins, Supervisors, and authorized Staff ---
$userRole = $_SESSION['role'] ?? null;
$staffCanManageInstruments = (isset($app_settings['staff_can_manage_instruments']) && $app_settings['staff_can_manage_instruments'] === '1');
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !(in_array($userRole, ['admin', 'supervisor']) || ($userRole === 'staff' && $staffCanManageInstruments))
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

// --- Pagination & Filter Configuration ---
$recordsPerPage = 15;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
$offset = ($currentPage - 1) * $recordsPerPage;

$instruments = [];
$totalRecords = 0;
$totalPages = 0;
$response = ['success' => false, 'data' => [], 'pagination' => []];

$conn = connectToDatabase();
if ($conn) {
    $baseSql = "FROM instruments i 
                LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
                LEFT JOIN departments d ON i.department_id = d.department_id
                LEFT JOIN users u ON i.created_by_user_id = u.user_id";
    $whereClause = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause .= " AND (i.instrument_name LIKE ? OR i.instrument_code LIKE ? OR it.type_name LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sss";
    }
    if (!empty($statusFilter)) {
        $whereClause .= " AND i.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    if ($typeFilter) {
        $whereClause .= " AND i.instrument_type_id = ?";
        $params[] = $typeFilter;
        $types .= "i";
    }
    if ($departmentFilter) {
        $whereClause .= " AND i.department_id = ?";
        $params[] = $departmentFilter;
        $types .= "i";
    }

    // Count total records
    $sqlCount = "SELECT COUNT(i.instrument_id) as total " . $baseSql . $whereClause;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    // Fetch data for the current page
    if ($totalRecords > 0) {
        // === PERUBAHAN DI SINI: Menambahkan i.notes ke SELECT ===
        $sqlData = "SELECT i.instrument_id, i.instrument_name, i.instrument_code, i.status, i.updated_at, i.image_filename, i.notes,
                           it.type_name, d.department_name, u.full_name as creator_name
                    " . $baseSql . $whereClause . "
                    ORDER BY i.instrument_name ASC
                    LIMIT ? OFFSET ?";
        
        array_push($params, $recordsPerPage, $offset);
        $types .= "ii";

        if ($stmtData = $conn->prepare($sqlData)) {
            $stmtData->bind_param($types, ...$params);
            $stmtData->execute();
            $result = $stmtData->get_result();
            while ($row = $result->fetch_assoc()) {
                // Tambahkan data yang sudah diproses untuk frontend
                $statusInfo = getUniversalStatusBadge($row['status']);
                $row['status_text'] = $statusInfo['text'];
                $row['status_class'] = $statusInfo['class'];
                $row['row_status_class'] = 'tr-status-' . str_replace(' ', '_', strtolower($row['status']));
                $instruments[] = $row;
            }
            $stmtData->close();
        }
    }

    $response = [
        'success' => true,
        'data' => $instruments,
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