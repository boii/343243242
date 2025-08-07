<?php
/**
 * Get Eligible Cycles for Merging (AJAX Endpoint)
 *
 * This version is modified to fetch 'completed' cycles from the same day
 * for a specific machine, allowing loads to be merged into already
 * finished batches. This replaces the 'pending_validation' logic.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   UI/UX Specialist
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once '../config.php';

header('Content-Type: application/json');

// Authorization check: Only logged-in users can access this data
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

$machineId = filter_input(INPUT_GET, 'machine_id', FILTER_VALIDATE_INT);

if (!$machineId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID Mesin tidak valid atau tidak disediakan.']);
    exit;
}

$response = ['success' => false, 'cycles' => []];
$conn = connectToDatabase();

if ($conn) {
    // We need to find cycles for the specific machine that are 'completed' today
    $stmtMachine = $conn->prepare("SELECT machine_name FROM machines WHERE machine_id = ?");
    $stmtMachine->bind_param("i", $machineId);
    $stmtMachine->execute();
    $resultMachine = $stmtMachine->get_result();

    if ($machine = $resultMachine->fetch_assoc()) {
        $machineName = $machine['machine_name'];
        
        // PERUBAHAN: Query diubah untuk mencari status 'completed' dan pada tanggal hari ini (CURDATE())
        $sql = "SELECT cycle_id, cycle_number FROM sterilization_cycles 
                WHERE machine_name = ? AND status = 'completed' AND DATE(cycle_date) = CURDATE()
                ORDER BY cycle_date DESC";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $machineName);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $response['cycles'][] = $row;
            }
            $stmt->close();
            $response['success'] = true;
        } else {
            $response['error'] = 'Gagal mempersiapkan statement untuk mengambil siklus.';
            http_response_code(500);
        }
    } else {
        $response['error'] = 'Mesin tidak ditemukan.';
        http_response_code(404);
    }
    $stmtMachine->close();
    $conn->close();
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
?>