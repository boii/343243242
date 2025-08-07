<?php
/**
 * Get Cycle Details (AJAX Endpoint - with Enriched Load Data)
 *
 * This version enriches the load data by including the creator's name,
 * creation timestamp, and destination department for each load within the cycle.
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

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'supervisor', 'staff'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

$cycleId = filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
if (!$cycleId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID Siklus tidak valid.']);
    exit;
}

$response = ['success' => false];
$conn = connectToDatabase();

if ($conn) {
    // 1. Fetch main cycle details
    $sqlCycle = "SELECT sc.*, u.full_name as operator_name 
                 FROM sterilization_cycles sc
                 LEFT JOIN users u ON sc.operator_user_id = u.user_id
                 WHERE sc.cycle_id = ?";
    $stmtCycle = $conn->prepare($sqlCycle);
    $stmtCycle->bind_param("i", $cycleId);
    $stmtCycle->execute();
    $resultCycle = $stmtCycle->get_result();

    if ($cycleData = $resultCycle->fetch_assoc()) {
        // 2. --- PERUBAHAN: Mengambil informasi tambahan untuk setiap muatan ---
        $sqlLoads = "SELECT 
                        sl.load_id, 
                        sl.load_name, 
                        sl.status, 
                        sl.created_at,
                        u.full_name as creator_name,
                        d.department_name as destination_department_name
                     FROM sterilization_loads sl
                     LEFT JOIN users u ON sl.created_by_user_id = u.user_id
                     LEFT JOIN departments d ON sl.destination_department_id = d.department_id
                     WHERE sl.cycle_id = ?
                     ORDER BY sl.created_at DESC";
        $stmtLoads = $conn->prepare($sqlLoads);
        $stmtLoads->bind_param("i", $cycleId);
        $stmtLoads->execute();
        $resultLoads = $stmtLoads->get_result();
        $loads = [];
        while($load = $resultLoads->fetch_assoc()) {
            $loads[] = $load;
        }

        $response['success'] = true;
        $response['cycle'] = $cycleData;
        $response['loads'] = $loads;

    } else {
        $response['error'] = 'Siklus tidak ditemukan.';
        http_response_code(404);
    }

    $stmtCycle->close();
    if(isset($stmtLoads)) $stmtLoads->close();
    $conn->close();
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);