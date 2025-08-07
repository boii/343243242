<?php
/**
 * Get Dashboard Statistics (Streamlined Workflow)
 *
 * Fetches aggregated data for dashboard cards and charts. This version
 * replaces the 'pending_labels' metric with 'cycles_today' to reflect
 * the removal of the manual validation step.
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

// --- Authorization Check: User must be logged in ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Akses ditolak. Sesi tidak valid.']);
    exit;
}

// Initialize the stats array with default values
$stats = [
    'active_labels' => 0,
    'available_instruments' => 0,
    'expired_labels' => 0,
    'cycles_today' => 0, // Menggantikan pending_labels
    'trends' => [
        'active_labels' => 0,
        'available_instruments' => 0,
        'expired_labels' => 0
    ],
    'label_status_counts' => [
        'active' => 0,
        'used' => 0,
        'expired' => 0,
        'recalled' => 0,
    ],
    'weekly_label_creation' => []
];

$conn = connectToDatabase();
if ($conn) {
    try {
        // 1. Get counts of labels by status for the doughnut chart
        $sqlStatus = "SELECT status, COUNT(record_id) as count FROM sterilization_records GROUP BY status";
        if ($resultStatus = $conn->query($sqlStatus)) {
            while ($row = $resultStatus->fetch_assoc()) {
                if (array_key_exists($row['status'], $stats['label_status_counts'])) {
                    $stats['label_status_counts'][$row['status']] = (int)$row['count'];
                }
            }
            $stats['active_labels'] = $stats['label_status_counts']['active'] ?? 0;
            $stats['expired_labels'] = $stats['label_status_counts']['expired'] ?? 0;
            $resultStatus->free();
        }

        // 2. PERUBAHAN: Get count of total cycles created today
        $sqlCyclesToday = "SELECT COUNT(cycle_id) as count FROM sterilization_cycles WHERE DATE(cycle_date) = CURDATE()";
        if ($resultCyclesToday = $conn->query($sqlCyclesToday)) {
            $stats['cycles_today'] = (int)($resultCyclesToday->fetch_assoc()['count'] ?? 0);
            $resultCyclesToday->free();
        }

        // 3. Get count of available instruments
        if ($resultInstruments = $conn->query("SELECT COUNT(instrument_id) as count FROM instruments WHERE status = 'tersedia'")) {
            $stats['available_instruments'] = (int)($resultInstruments->fetch_assoc()['count'] ?? 0);
            $resultInstruments->free();
        }
        
        // 4. Get trend data (tidak berubah)
        $today_start = (new DateTime())->format('Y-m-d 00:00:00');
        $yesterday_start = (new DateTime())->modify('-1 day')->format('Y-m-d 00:00:00');
        $trendQueries = [
            'active_labels' => "SELECT COUNT(record_id) as count FROM sterilization_records WHERE status = 'active' AND created_at >= ?",
            'available_instruments' => "SELECT COUNT(instrument_id) as count FROM instruments WHERE status = 'tersedia' AND created_at >= ?",
            'expired_labels' => "SELECT COUNT(record_id) as count FROM sterilization_records WHERE status = 'expired' AND expiry_date >= ?"
        ];

        foreach($trendQueries as $key => $sql) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $today_start);
            $stmt->execute();
            $today_count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
            
            $stmt_yesterday = $conn->prepare(str_replace('>= ?', 'BETWEEN ? AND ?', $sql));
            $stmt_yesterday->bind_param("ss", $yesterday_start, $today_start);
            $stmt_yesterday->execute();
            $yesterday_count = (int)($stmt_yesterday->get_result()->fetch_assoc()['count'] ?? 0);
            
            $stats['trends'][$key] = $today_count - $yesterday_count;
            $stmt->close();
            $stmt_yesterday->close();
        }

        // 5. Get weekly label creation data (tidak berubah)
        $weeklyData = [];
        $dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new DateTime())->modify("-$i days");
            $dateKey = $date->format('Y-m-d');
            $dayLabel = $dayNames[(int)$date->format('w')];
            $weeklyData[$dateKey] = ['day' => $dayLabel, 'count' => 0];
        }
        
        $sevenDaysAgo = (new DateTime())->modify('-6 days')->format('Y-m-d 00:00:00');
        $sqlWeekly = "SELECT DATE(created_at) as creation_date, COUNT(record_id) as count 
                      FROM sterilization_records 
                      WHERE created_at >= ? 
                      GROUP BY DATE(created_at)";
        
        if ($stmtWeekly = $conn->prepare($sqlWeekly)) {
            $stmtWeekly->bind_param("s", $sevenDaysAgo);
            $stmtWeekly->execute();
            $resultWeekly = $stmtWeekly->get_result();
            while ($row = $resultWeekly->fetch_assoc()) {
                if (isset($weeklyData[$row['creation_date']])) {
                    $weeklyData[$row['creation_date']]['count'] = (int)$row['count'];
                }
            }
            $stmtWeekly->close();
        }
        $stats['weekly_label_creation'] = array_values($weeklyData);

    } catch (Exception $e) {
        error_log("Dashboard Stats Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Gagal mengambil data statistik.']);
        exit;
    } finally {
        $conn->close();
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal.']);
    exit;
}

echo json_encode($stats);
exit;