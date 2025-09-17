<?php
/**
 * Get Label History Data (AJAX Endpoint)
 *
 * Fetches and returns a paginated list of sterilization records (labels)
 * with support for extensive filtering.
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

// --- Pagination & Filter Configuration ---
$recordsPerPage = 15;
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($currentPage - 1) * $recordsPerPage;

$searchQuery = trim($_GET['search_query'] ?? '');
$dateStart = trim($_GET['date_start'] ?? '');
$dateEnd = trim($_GET['date_end'] ?? '');
$itemTypeFilter = trim($_GET['item_type'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$departmentFilter = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT); // Filter baru

$response = ['success' => false, 'data' => [], 'pagination' => []];
$conn = connectToDatabase();

if ($conn) {
    // Automatically update status to 'expired' just in time for the query
    $conn->query("UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW()");

    $baseJoins = "FROM sterilization_records sr
                  LEFT JOIN users u ON sr.created_by_user_id = u.user_id
                  LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
                  LEFT JOIN departments d ON sr.destination_department_id = d.department_id";

    $whereClause = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($searchQuery)) {
        $searchTerm = "%" . $searchQuery . "%";
        $whereClause .= " AND (sr.label_unique_id LIKE ? OR sr.label_title LIKE ? OR sl.load_name LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= "sss";
    }
    if (!empty($dateStart)) { $whereClause .= " AND DATE(sr.created_at) >= ?"; $params[] = $dateStart; $types .= "s"; }
    if (!empty($dateEnd)) { $whereClause .= " AND DATE(sr.created_at) <= ?"; $params[] = $dateEnd; $types .= "s"; }
    if (!empty($itemTypeFilter)) { $whereClause .= " AND sr.item_type = ?"; $params[] = $itemTypeFilter; $types .= "s"; }
    if (!empty($statusFilter)) { $whereClause .= " AND sr.status = ?"; $params[] = $statusFilter; $types .= "s"; }
    if ($departmentFilter) { $whereClause .= " AND sr.destination_department_id = ?"; $params[] = $departmentFilter; $types .= "i"; } // Filter baru ditambahkan

    // Count total records
    $sqlCount = "SELECT COUNT(sr.record_id) as total " . $baseJoins . $whereClause;
    $totalRecords = 0;
    if ($stmtCount = $conn->prepare($sqlCount)) {
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $totalPages = (int)ceil($totalRecords / $recordsPerPage);
        $stmtCount->close();
    }

    // Fetch data for the current page
    $labels = [];
    $setIdsToCheck = [];
    if ($totalRecords > 0) {
        // --- PERUBAHAN: Menambahkan sr.return_condition ke SELECT ---
        $sqlData = "SELECT sr.record_id, sr.label_unique_id, sr.item_type, sr.item_id, sr.label_items_snapshot, sr.label_title, sr.created_at, sr.expiry_date, sr.status, sr.return_condition,
                           u.full_name as creator_name, sl.load_name, d.department_name as destination_department_name
                    {$baseJoins}
                    {$whereClause}
                    ORDER BY sr.created_at DESC
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
                $row['row_status_class'] = 'tr-status-' . str_replace([' ', '_'], '-', strtolower($row['status']));
                $labels[] = $row;
                if ($row['item_type'] === 'set') {
                    $setIdsToCheck[] = $row['item_id'];
                }
            }
            $stmtData->close();
        }
    }

    // --- Logika untuk membandingkan snapshot dengan master set ---
    $masterSetsData = [];
    if (!empty($setIdsToCheck)) {
        $uniqueSetIds = array_unique($setIdsToCheck);
        $setPlaceholders = implode(',', array_fill(0, count($uniqueSetIds), '?'));
        $setTypes = str_repeat('i', count($uniqueSetIds));

        $sqlMaster = "SELECT set_id, instrument_id, quantity FROM instrument_set_items WHERE set_id IN ($setPlaceholders)";
        if ($stmtMaster = $conn->prepare($sqlMaster)) {
            $stmtMaster->bind_param($setTypes, ...$uniqueSetIds);
            $stmtMaster->execute();
            $resultMaster = $stmtMaster->get_result();
            while($item = $resultMaster->fetch_assoc()) {
                $masterSetsData[$item['set_id']][] = ['instrument_id' => (int)$item['instrument_id'], 'quantity' => (int)$item['quantity']];
            }
            $stmtMaster->close();
        }
    }

    foreach ($labels as &$label) {
        $label['is_modified'] = false; // Default
        if ($label['item_type'] === 'set') {
            $snapshot = json_decode($label['label_items_snapshot'] ?? '[]', true);
            $master = $masterSetsData[$label['item_id']] ?? [];

            // Normalisasi: urutkan berdasarkan instrument_id untuk perbandingan yang konsisten
            usort($snapshot, fn($a, $b) => $a['instrument_id'] <=> $b['instrument_id']);
            usort($master, fn($a, $b) => $a['instrument_id'] <=> $b['instrument_id']);

            // Konversi ke int untuk konsistensi tipe data dari JSON
            $snapshotNormalized = array_map(function($item) {
                return ['instrument_id' => (int)$item['instrument_id'], 'quantity' => (int)$item['quantity']];
            }, $snapshot);

            if (json_encode($snapshotNormalized) !== json_encode($master)) {
                $label['is_modified'] = true;
            }
        }
    }
    unset($label); // Hapus referensi terakhir

    $response = [
        'success' => true,
        'data' => $labels,
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
