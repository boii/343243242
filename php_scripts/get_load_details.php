<?php
/**
 * Get Sterilization Load Details (AJAX Endpoint - Final Intelligent Version)
 *
 * This version is corrected to align with the simplified database schema
 * by removing session and method logic.
 * It now also includes expiry date prediction for each item.
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

$loadId = filter_input(INPUT_GET, 'load_id', FILTER_VALIDATE_INT);
if (!$loadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID Muatan tidak valid.']);
    exit;
}

$response = ['success' => false];
$conn = connectToDatabase();

if ($conn) {
    try {
        global $app_settings;
        $globalDefaultExpiryDays = (int)($app_settings['default_expiry_days'] ?? 30);

        $sqlLoad = "SELECT
                        sl.*, u.full_name as creator_name, sc.cycle_number,
                        m.machine_name,
                        dept.department_name as destination_department_name
                    FROM sterilization_loads sl
                    LEFT JOIN users u ON sl.created_by_user_id = u.user_id
                    LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
                    LEFT JOIN machines m ON sl.machine_id = m.machine_id
                    LEFT JOIN departments dept ON sl.destination_department_id = dept.department_id
                    WHERE sl.load_id = ?";

        $stmtLoad = $conn->prepare($sqlLoad);
        if ($stmtLoad === false) throw new Exception("Gagal mempersiapkan query muatan: " . $conn->error);

        $stmtLoad->bind_param("i", $loadId);
        $stmtLoad->execute();
        $resultLoad = $stmtLoad->get_result();

        if ($loadData = $resultLoad->fetch_assoc()) {
            $sqlItems = "SELECT
                            li.load_item_id, li.item_id, li.item_type, li.item_snapshot,
                            CASE li.item_type WHEN 'instrument' THEN i.instrument_name ELSE s.set_name END as item_name,
                            CASE li.item_type WHEN 'instrument' THEN i.instrument_code ELSE s.set_code END as item_code
                         FROM sterilization_load_items li
                         LEFT JOIN instruments i ON li.item_type = 'instrument' AND li.item_id = i.instrument_id
                         LEFT JOIN instrument_sets s ON li.item_type = 'set' AND li.item_id = s.set_id
                         WHERE li.load_id = ? ORDER BY item_name ASC";

            $stmtItems = $conn->prepare($sqlItems);
            if ($stmtItems === false) throw new Exception("Gagal mempersiapkan query item muatan: " . $conn->error);

            $stmtItems->bind_param("i", $loadId);
            $stmtItems->execute();
            $itemsFromDb = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtItems->close();

            // --- Logika Prediksi Kedaluwarsa Dimulai Di Sini ---
            $setIds = []; $instrumentIds = [];
            foreach ($itemsFromDb as $item) {
                if ($item['item_type'] === 'set') {
                    $setIds[] = $item['item_id'];
                    if (!empty($item['item_snapshot'])) {
                        $snapshot = json_decode($item['item_snapshot'], true);
                        if (is_array($snapshot)) $instrumentIds = array_merge($instrumentIds, array_column($snapshot, 'instrument_id'));
                    }
                } else {
                    $instrumentIds[] = $item['item_id'];
                }
            }
            $instrumentIds = array_unique(array_filter($instrumentIds)); $setIds = array_unique(array_filter($setIds));

            $masterData = ['instrument' => [], 'set' => []];
            if (!empty($instrumentIds)) {
                $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
                $stmtMasterInst = $conn->prepare("SELECT instrument_id, expiry_in_days FROM instruments WHERE instrument_id IN ($placeholders)");
                $stmtMasterInst->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
                $stmtMasterInst->execute();
                $resInst = $stmtMasterInst->get_result();
                while ($row = $resInst->fetch_assoc()) $masterData['instrument'][$row['instrument_id']] = $row;
                $stmtMasterInst->close();
            }
            if (!empty($setIds)) {
                $placeholders = implode(',', array_fill(0, count($setIds), '?'));
                $stmtMasterSet = $conn->prepare("SELECT set_id, expiry_in_days FROM instrument_sets WHERE set_id IN ($placeholders)");
                $stmtMasterSet->bind_param(str_repeat('i', count($setIds)), ...$setIds);
                $stmtMasterSet->execute();
                $resSet = $stmtMasterSet->get_result();
                while ($row = $resSet->fetch_assoc()) $masterData['set'][$row['set_id']] = $row;
                $stmtMasterSet->close();
            }

            foreach($itemsFromDb as &$item) {
                $daysUntilExpiry = $globalDefaultExpiryDays;
                if ($item['item_type'] === 'instrument') {
                    $daysUntilExpiry = (int)($masterData['instrument'][$item['item_id']]['expiry_in_days'] ?? $globalDefaultExpiryDays);
                } elseif ($item['item_type'] === 'set') {
                    $setSpecificExpiry = (int)($masterData['set'][$item['item_id']]['expiry_in_days'] ?? 0);
                    $instrumentMinExpiry = null;
                    if (!empty($item['item_snapshot'])) {
                        $snapshot = json_decode($item['item_snapshot'], true);
                        if (is_array($snapshot) && !empty($snapshot)) {
                            $expiryValues = [];
                            foreach ($snapshot as $snapItem) {
                                $expiryValues[] = (int)($masterData['instrument'][(int)$snapItem['instrument_id']]['expiry_in_days'] ?? $globalDefaultExpiryDays);
                            }
                            if (!empty($expiryValues)) $instrumentMinExpiry = min($expiryValues);
                        }
                    }
                    if ($setSpecificExpiry > 0) $daysUntilExpiry = $setSpecificExpiry;
                    elseif ($instrumentMinExpiry !== null) $daysUntilExpiry = $instrumentMinExpiry;
                }
                $item['predicted_expiry_days'] = $daysUntilExpiry;
            }
            unset($item);
            // --- Logika Prediksi Selesai ---

            $loadData['items'] = $itemsFromDb;

            $stmtQueueCheck = $conn->prepare("SELECT COUNT(queue_id) as count FROM print_queue WHERE load_id = ?");
            $stmtQueueCheck->bind_param("i", $loadId);
            $stmtQueueCheck->execute();
            $loadData['has_print_queue_items'] = (bool)($stmtQueueCheck->get_result()->fetch_assoc()['count'] > 0);
            $stmtQueueCheck->close();

            $response['success'] = true;
            $response['load'] = $loadData;

        } else {
            $response['error'] = 'Muatan tidak ditemukan.';
            http_response_code(404);
        }
        $stmtLoad->close();
    } catch (Exception $e) {
        $response['error'] = "Terjadi kesalahan pada server: " . $e->getMessage();
        http_response_code(500);
    } finally {
        if ($conn) $conn->close();
    }
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
