<?php
/**
 * Get Enhanced Report & Statistics Data (AJAX Endpoint)
 *
 * Fetches comprehensive, aggregated data for the main reports dashboard,
 * including KPIs, chart data, and a detailed expiry report.
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
        $kpis = [];

        // 1. KPI: Cycle Success Rate
        $sqlCycleStatus = "SELECT status, COUNT(cycle_id) as count 
                           FROM sterilization_cycles 
                           WHERE status IN ('completed', 'failed') 
                           GROUP BY status";
        $resultCycleStatus = $conn->query($sqlCycleStatus);
        $cycleCounts = ['completed' => 0, 'failed' => 0];
        while($row = $resultCycleStatus->fetch_assoc()) {
            $cycleCounts[$row['status']] = (int)$row['count'];
        }
        $totalCycles = $cycleCounts['completed'] + $cycleCounts['failed'];
        $kpis['cycle_success_rate'] = ($totalCycles > 0) ? ($cycleCounts['completed'] / $totalCycles) * 100 : 0;

        // 2. KPI: Instruments (Available & Needing Attention) & Status Distribution Chart
        $sqlInstrumentStatus = "SELECT status, COUNT(instrument_id) as count FROM instruments GROUP BY status";
        $resultInstrumentStatus = $conn->query($sqlInstrumentStatus);
        $instrumentStatusCounts = ['tersedia' => 0, 'perbaikan' => 0, 'rusak' => 0, 'sterilisasi' => 0];
        while($row = $resultInstrumentStatus->fetch_assoc()) {
            if (array_key_exists($row['status'], $instrumentStatusCounts)) {
                $instrumentStatusCounts[$row['status']] = (int)$row['count'];
            }
        }
        $kpis['instruments_available'] = $instrumentStatusCounts['tersedia'];
        $kpis['instruments_needing_attention'] = $instrumentStatusCounts['perbaikan'] + $instrumentStatusCounts['rusak'];
        
        $reportData['instrument_status_distribution'] = [];
        foreach($instrumentStatusCounts as $status => $count) {
             $reportData['instrument_status_distribution'][] = ['status' => $status, 'count' => $count];
        }

        // 3. Label Creation Trend (Last 30 Days)
        $thirtyDaysAgo = (new DateTime())->modify('-29 days')->format('Y-m-d');
        $sqlLabelTrend = "SELECT DATE(created_at) as creation_date, COUNT(record_id) as count 
                          FROM sterilization_records 
                          WHERE created_at >= ?
                          GROUP BY DATE(created_at)
                          ORDER BY creation_date ASC";
        $stmtLabelTrend = $conn->prepare($sqlLabelTrend);
        $stmtLabelTrend->bind_param("s", $thirtyDaysAgo);
        $stmtLabelTrend->execute();
        $reportData['label_creation_trend'] = $stmtLabelTrend->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtLabelTrend->close();
        
        // 4. Detailed Expiry Report & KPI for items expiring soon
        $expiryDaysWarning = 30;
        $warningDate = (new DateTime())->modify("+$expiryDaysWarning days")->format('Y-m-d H:i:s');
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $nowDate = new DateTime();

        $sqlExpiry = "SELECT 
                        sr.label_unique_id, 
                        sr.label_title, 
                        sr.expiry_date,
                        sr.status,
                        d.department_name as destination_department_name,
                        CASE sr.item_type WHEN 'instrument' THEN i.instrument_name ELSE s.set_name END as item_name
                      FROM sterilization_records sr
                      LEFT JOIN instruments i ON sr.item_type = 'instrument' AND sr.item_id = i.instrument_id
                      LEFT JOIN instrument_sets s ON sr.item_type = 'set' AND sr.item_id = s.set_id
                      LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
                      LEFT JOIN departments d ON sl.destination_department_id = d.department_id
                      WHERE sr.status = 'active' AND sr.expiry_date < ?
                      ORDER BY sr.expiry_date ASC";
        
        $stmtExpiry = $conn->prepare($sqlExpiry);
        $stmtExpiry->bind_param("s", $warningDate);
        $stmtExpiry->execute();
        $resultExpiry = $stmtExpiry->get_result();
        
        $expiryReportData = [];
        $expiringSoonCount = 0;
        while($row = $resultExpiry->fetch_assoc()){
            $expiryDate = new DateTime($row['expiry_date']);
            $diff = $nowDate->diff($expiryDate);
            $row['days_left'] = (int)$diff->format('%r%a');
            $expiryReportData[] = $row;
            if ($row['days_left'] >= 0) {
                $expiringSoonCount++;
            }
        }
        $kpis['items_expiring_soon'] = $expiringSoonCount;
        $reportData['expiry_report'] = $expiryReportData;
        $stmtExpiry->close();

        // Finalize response
        $reportData['kpis'] = $kpis;
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