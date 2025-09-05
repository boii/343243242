<?php
/**
 * Get Sterilization Loads Data (AJAX - Simplified)
 *
 * This version is updated to retrieve and filter by the new packaging type.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

// --- Filter & Pagination Parameters ---
$recordsPerPage = 15;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$machineFilter = filter_input(INPUT_GET, 'machine_id', FILTER_VALIDATE_INT);
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT);
$packagingFilter = filter_input(INPUT_GET, 'packaging_type_id', FILTER_VALIDATE_INT);
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$offset = ($currentPage - 1) * $recordsPerPage;

$response = ['success' => false, 'data' => [], 'pagination' => []];

$conn = connectToDatabase();
if ($conn) {
    $baseJoins = "FROM sterilization_loads sl
                  LEFT JOIN users u ON sl.created_by_user_id = u.user_id
                  LEFT JOIN machines m ON sl.machine_id = m.machine_id
                  LEFT JOIN departments d ON sl.destination_department_id = d.department_id
                  LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
                  LEFT JOIN sterilization_load_items sli ON sl.load_id = sli.load_id
                  LEFT JOIN packaging_types pt ON sl.packaging_type_id = pt.packaging_type_id"; // PERUBAHAN

    $whereClause = " WHERE 1=1";
    $params = [];
    $types = "";
    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause .= " AND (sl.load_name LIKE ? OR m.machine_name LIKE ? OR d.department_name LIKE ? OR u.full_name LIKE ? OR sc.cycle_number LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sssss";
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
    // PERUBAHAN: Menambahkan filter jenis kemasan
    if ($packagingFilter) {
        $whereClause .= " AND sl.packaging_type_id = ?";
        $params[] = $packagingFilter;
        $types .= "i";
    }
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

    $sqlCount = "SELECT COUNT(DISTINCT sl.load_id) as total " . $baseJoins . $whereClause;
    $totalRecords = 0;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    $loads = [];
    if ($totalRecords > 0) {
        $sqlData = "SELECT 
                        sl.load_id, 
                        sl.load_name, 
                        sl.status, 
                        sl.created_at,
                        sl.notes,
                        u.full_name as creator_name,
                        m.machine_name,
                        sc.cycle_number,
                        d.department_name as destination_department_name,
                        pt.packaging_name, -- PERUBAHAN
                        COUNT(sli.load_item_id) as item_count
                    {$baseJoins}
                    {$whereClause}
                    GROUP BY sl.load_id
                    ORDER BY sl.created_at DESC
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
                $row['row_status_class'] = 'tr-status-' . str_replace(' ', '_', strtolower($row['status']));
                $loads[] = $row;
            }
            $stmtData->close();
        }
    }

    $response = [
        'success' => true,
        'data' => $loads,
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