<?php
/**
 * Get Full Reports & Analytics Data (AJAX Endpoint)
 *
 * Fetches and aggregates a comprehensive set of data for the main analytics dashboard.
 * This script is the single source of truth for reports.php.
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

// Authorization: Only Admins and Supervisors can view reports
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

$response = ['success' => false, 'data' => []];
$conn = connectToDatabase();

if ($conn) {
    try {
        $reportData = [];
        $thirtyDaysAgo = (new DateTime())->modify('-30 days')->format('Y-m-d');

        // 1. KPI Overview
        $sqlKpi = "SELECT 
                        (SELECT COUNT(cycle_id) FROM sterilization_cycles WHERE cycle_date >= ?) as cycles_this_month,
                        (SELECT AVG(TIMESTAMPDIFF(HOUR, sl.created_at, sc.cycle_date)) FROM sterilization_loads sl JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id WHERE sl.status = 'selesai' AND sc.cycle_date >= ?) as avg_processing_time_hours,
                        (SELECT COUNT(record_id) FROM sterilization_records WHERE created_at >= ?) as labels_created_this_month,
                        (SELECT COUNT(DISTINCT user_id) FROM users) as total_users";
        $stmtKpi = $conn->prepare($sqlKpi);
        $stmtKpi->bind_param("sss", $thirtyDaysAgo, $thirtyDaysAgo, $thirtyDaysAgo);
        $stmtKpi->execute();
        $reportData['kpi_overview'] = $stmtKpi->get_result()->fetch_assoc();
        $stmtKpi->close();

        // 2. Cycle Success Rate (All time)
        $sqlCycleRate = "SELECT status, COUNT(cycle_id) as count FROM sterilization_cycles WHERE status IN ('completed', 'failed') GROUP BY status";
        $resultCycleRate = $conn->query($sqlCycleRate);
        $cycleRates = $resultCycleRate->fetch_all(MYSQLI_ASSOC);
        $completed = 0; $failed = 0;
        foreach($cycleRates as $rate) {
            if ($rate['status'] === 'completed') $completed = (int)$rate['count'];
            if ($rate['status'] === 'failed') $failed = (int)$rate['count'];
        }
        $totalCycles = $completed + $failed;
        $reportData['kpi_overview']['success_rate'] = ($totalCycles > 0) ? round(($completed / $totalCycles) * 100, 1) : 0;


        // 3. Sterilization Activity (Last 30 days)
        $sqlActivity = "SELECT DATE(cycle_date) as date, COUNT(cycle_id) as count 
                        FROM sterilization_cycles 
                        WHERE cycle_date >= ?
                        GROUP BY DATE(cycle_date) ORDER BY date ASC";
        $stmtActivity = $conn->prepare($sqlActivity);
        $stmtActivity->bind_param("s", $thirtyDaysAgo);
        $stmtActivity->execute();
        $reportData['cycle_activity'] = $stmtActivity->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtActivity->close();
        
        // 4. Label Status Distribution
        $sqlLabelStatus = "SELECT status, COUNT(record_id) as count FROM sterilization_records GROUP BY status";
        $reportData['label_status_distribution'] = $conn->query($sqlLabelStatus)->fetch_all(MYSQLI_ASSOC);

        // 5. Machine Performance
        $sqlMachinePerf = "SELECT 
                                m.machine_name, 
                                COUNT(sc.cycle_id) as total_cycles,
                                SUM(CASE WHEN sc.status = 'completed' THEN 1 ELSE 0 END) as successful_cycles,
                                SUM(CASE WHEN sc.status = 'failed' THEN 1 ELSE 0 END) as failed_cycles
                           FROM machines m
                           LEFT JOIN sterilization_cycles sc ON m.machine_name = sc.machine_name
                           GROUP BY m.machine_id, m.machine_name
                           ORDER BY total_cycles DESC";
        $reportData['machine_performance'] = $conn->query($sqlMachinePerf)->fetch_all(MYSQLI_ASSOC);

        // 6. User Productivity
        $sqlUserPerf = "SELECT 
                            u.full_name,
                            (SELECT COUNT(load_id) FROM sterilization_loads WHERE created_by_user_id = u.user_id) as loads_created,
                            (SELECT COUNT(cycle_id) FROM sterilization_cycles WHERE operator_user_id = u.user_id) as cycles_operated,
                            (SELECT COUNT(record_id) FROM sterilization_records WHERE validated_by_user_id = u.user_id) as labels_validated
                        FROM users u
                        ORDER BY loads_created DESC, cycles_operated DESC";
        $reportData['user_productivity'] = $conn->query($sqlUserPerf)->fetch_all(MYSQLI_ASSOC);
        
        // 7. Inventory Insights - Most Sterilized Items
        $sqlMostUsed = "SELECT 
                            item_name, 
                            item_type, 
                            COUNT(record_id) as sterilization_count 
                        FROM (
                            SELECT 
                                CASE sr.item_type WHEN 'instrument' THEN i.instrument_name ELSE s.set_name END as item_name,
                                sr.item_type,
                                sr.record_id
                            FROM sterilization_records sr
                            LEFT JOIN instruments i ON sr.item_type = 'instrument' AND sr.item_id = i.instrument_id
                            LEFT JOIN instrument_sets s ON sr.item_type = 'set' AND sr.item_id = s.set_id
                        ) as item_details
                        WHERE item_name IS NOT NULL
                        GROUP BY item_name, item_type 
                        ORDER BY sterilization_count DESC
                        LIMIT 10";
        $reportData['inventory_most_sterilized'] = $conn->query($sqlMostUsed)->fetch_all(MYSQLI_ASSOC);

        // 8. Problematic Items
        $sqlProblematic = "SELECT instrument_id, instrument_name, status, notes, updated_at FROM instruments WHERE status = 'rusak' ORDER BY updated_at DESC";
        $reportData['inventory_problematic'] = $conn->query($sqlProblematic)->fetch_all(MYSQLI_ASSOC);

        $response['success'] = true;
        $response['data'] = $reportData;

    } catch (Exception $e) {
        $response['error'] = 'Gagal mengambil data laporan: ' . $e->getMessage();
        http_response_code(500);
    } finally {
        $conn->close();
    }
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
exit;