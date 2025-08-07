<?php
/**
 * Get Users Data (AJAX Endpoint)
 *
 * Fetches and returns a paginated list of users as a JSON object.
 * This version grants access to supervisors.
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

// --- MODIFICATION START: Allow 'supervisor' to access this data ---
// Authorization check - Admins and Supervisors can access user data
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION["role"], ['admin', 'supervisor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}
// --- MODIFICATION END ---


// --- Pagination & Search Configuration ---
$recordsPerPage = 10;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$searchQuery = trim($_GET['search'] ?? '');
$offset = ($currentPage - 1) * $recordsPerPage;

$users = [];
$totalRecords = 0;
$totalPages = 0;
$response = ['success' => false, 'data' => [], 'pagination' => []];

$conn = connectToDatabase();
if ($conn) {
    $whereClause = "";
    $params = [];
    $types = "";

    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause = " WHERE (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sss";
    }

    // Count total records
    $sqlCount = "SELECT COUNT(user_id) as total FROM users" . $whereClause;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    // Fetch data for the current page
    if ($totalRecords > 0) {
        $sqlData = "SELECT user_id, full_name, username, email, role, created_at 
                    FROM users 
                    $whereClause
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
        
        array_push($params, $recordsPerPage, $offset);
        $types .= "ii";

        if ($stmtData = $conn->prepare($sqlData)) {
            $stmtData->bind_param($types, ...$params);
            $stmtData->execute();
            $result = $stmtData->get_result();
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmtData->close();
        }
    }

    $response = [
        'success' => true,
        'data' => $users,
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