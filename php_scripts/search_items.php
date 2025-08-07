<?php
/**
 * Search Items API Endpoint (for AJAX)
 *
 * Fetches instruments, sets, or both based on a search query.
 * If 'type' is not specified, it searches both.
 * This version returns structured data for rich UI rendering.
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
$results = [];

if (empty($query)) {
    echo json_encode($results);
    exit;
}

$conn = connectToDatabase();

if ($conn) {
    $searchTerm = "%" . $query . "%";
    $params = [];
    $types = "";

    // --- PERUBAHAN LOGIKA DIMULAI DI SINI ---
    $sqlSet = "SELECT 'set' as type, set_id as id, set_name as name, set_code as code FROM instrument_sets WHERE (set_name LIKE ? OR set_code LIKE ?)";
    $sqlInstrument = "SELECT 'instrument' as type, instrument_id as id, instrument_name as name, instrument_code as code FROM instruments WHERE (instrument_name LIKE ? OR instrument_code LIKE ?)";

    $sql = "($sqlSet) UNION ALL ($sqlInstrument) ORDER BY name ASC LIMIT 20";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = "ssss";
    // --- PERUBAHAN LOGIKA SELESAI ---

    if (!empty($sql) && $stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'name' => $row['name'],
                'code' => $row['code'] ?? ''
            ];
        }
        $stmt->close();
    }
    $conn->close();
}

echo json_encode($results);
exit;