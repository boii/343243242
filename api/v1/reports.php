<?php
// /api/v1/reports.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk data laporan & KPI.
 *
 * @param mysqli $conn Koneksi database.
 * @return void
 */
function handleGetReports(mysqli $conn): void
{
    try {
        $reportData = [];

        // KPI: Cycle Success Rate
        $sqlCycleStatus = "SELECT status, COUNT(cycle_id) as count FROM sterilization_cycles WHERE status IN ('completed', 'failed') GROUP BY status";
        $resultCycleStatus = $conn->query($sqlCycleStatus);
        $cycleCounts = ['completed' => 0, 'failed' => 0];
        while($row = $resultCycleStatus->fetch_assoc()) {
            $cycleCounts[$row['status']] = (int)$row['count'];
        }
        $totalCycles = $cycleCounts['completed'] + $cycleCounts['failed'];
        $reportData['kpi_cycle_success_rate_percent'] = ($totalCycles > 0) ? round(($cycleCounts['completed'] / $totalCycles) * 100, 2) : 0;

        // KPI: Items Expiring Soon (30 days)
        $warningDate = (new DateTime())->modify("+30 days")->format('Y-m-d H:i:s');
        $stmtExpiry = $conn->prepare("SELECT COUNT(record_id) as count FROM sterilization_records WHERE status = 'active' AND expiry_date < ?");
        $stmtExpiry->bind_param("s", $warningDate);
        $stmtExpiry->execute();
        $reportData['kpi_items_expiring_soon_count'] = (int) $stmtExpiry->get_result()->fetch_assoc()['count'];
        $stmtExpiry->close();

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $reportData]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal mengambil data laporan: ' . $e->getMessage()]);
    }
}