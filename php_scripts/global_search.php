<?php
/**
 * Global Search API Endpoint (for AJAX)
 *
 * Fetches search results from multiple tables (instruments, sets, loads, labels)
 * based on a single query and returns them as a grouped JSON object.
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

// Authorization: User must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$query = trim($_GET['query'] ?? '');
$results = [
    'instruments' => [],
    'sets' => [],
    'loads' => [],
    'labels' => [],
];

if (strlen($query) < 2) {
    echo json_encode($results);
    exit;
}

$conn = connectToDatabase();

if ($conn) {
    $searchTerm = "%" . $query . "%";
    $limitPerCategory = 5;

    // Search Instruments
    $sqlInstruments = "SELECT instrument_id as id, instrument_name as name, instrument_code as code
                       FROM instruments 
                       WHERE instrument_name LIKE ? OR instrument_code LIKE ? 
                       ORDER BY instrument_name ASC
                       LIMIT ?";
    if ($stmt = $conn->prepare($sqlInstruments)) {
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limitPerCategory);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['url'] = "instrument_detail.php?instrument_id=" . $row['id'];
            $results['instruments'][] = $row;
        }
        $stmt->close();
    }

    // Search Sets
    $sqlSets = "SELECT set_id as id, set_name as name, set_code as code
                FROM instrument_sets 
                WHERE set_name LIKE ? OR set_code LIKE ?
                ORDER BY set_name ASC
                LIMIT ?";
    if ($stmt = $conn->prepare($sqlSets)) {
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limitPerCategory);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['url'] = "set_detail.php?set_id=" . $row['id'];
            $results['sets'][] = $row;
        }
        $stmt->close();
    }

    // Search Loads
    $sqlLoads = "SELECT load_id as id, load_name as name
                 FROM sterilization_loads 
                 WHERE load_name LIKE ?
                 ORDER BY created_at DESC
                 LIMIT ?";
    if ($stmt = $conn->prepare($sqlLoads)) {
        $stmt->bind_param("si", $searchTerm, $limitPerCategory);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['url'] = "load_detail.php?load_id=" . $row['id'];
            $results['loads'][] = $row;
        }
        $stmt->close();
    }

    // Search Labels by Unique ID or Title
    $sqlLabels = "SELECT record_id as id, label_unique_id, label_title
                  FROM sterilization_records
                  WHERE label_unique_id LIKE ? OR label_title LIKE ?
                  ORDER BY created_at DESC
                  LIMIT ?";
    if ($stmt = $conn->prepare($sqlLabels)) {
        $stmt->bind_param("ssi", $searchTerm, $searchTerm, $limitPerCategory);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['name'] = !empty($row['label_title']) ? $row['label_title'] : 'Label Tanpa Judul';
            $row['code'] = $row['label_unique_id'];
            $row['url'] = "verify_label.php?uid=" . urlencode($row['label_unique_id']);
            $results['labels'][] = $row;
        }
        $stmt->close();
    }

    $conn->close();
}

echo json_encode($results);
exit;