<?php
/**
 * Get Sterilization Load Details (AJAX Endpoint - Final Intelligent Version)
 *
 * This version is updated to retrieve and include packaging type information
 * from the new 'packaging_types' table.
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
        // PERUBAHAN: Menambahkan `pt.packaging_name` dan JOIN ke tabel `packaging_types`
        $sqlLoad = "SELECT 
                        sl.*, u.full_name as creator_name, sc.cycle_number,
                        m.machine_name,
                        dept.department_name as destination_department_name,
                        pt.packaging_name
                    FROM sterilization_loads sl 
                    LEFT JOIN users u ON sl.created_by_user_id = u.user_id 
                    LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
                    LEFT JOIN machines m ON sl.machine_id = m.machine_id
                    LEFT JOIN departments dept ON sl.destination_department_id = dept.department_id
                    LEFT JOIN packaging_types pt ON sl.packaging_type_id = pt.packaging_type_id
                    WHERE sl.load_id = ?";
        
        $stmtLoad = $conn->prepare($sqlLoad);
        if ($stmtLoad === false) throw new Exception("Gagal mempersiapkan query muatan: " . $conn->error);
        
        $stmtLoad->bind_param("i", $loadId);
        $stmtLoad->execute();
        $resultLoad = $stmtLoad->get_result();
        
        if ($loadData = $resultLoad->fetch_assoc()) {
            $loadData['items'] = [];

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
            $resultItems = $stmtItems->get_result();
            $itemsFromDb = $resultItems->fetch_all(MYSQLI_ASSOC);
            $stmtItems->close();

            $allInstrumentIdsToCheck = [];
            foreach ($itemsFromDb as $item) {
                if ($item['item_type'] === 'instrument') {
                    $allInstrumentIdsToCheck[] = $item['item_id'];
                } elseif ($item['item_type'] === 'set' && !empty($item['item_snapshot'])) {
                    $snapshot = json_decode($item['item_snapshot'], true);
                    if (is_array($snapshot)) {
                        $allInstrumentIdsToCheck = array_merge($allInstrumentIdsToCheck, array_column($snapshot, 'instrument_id'));
                    }
                }
            }
            $allInstrumentIdsToCheck = array_unique(array_filter($allInstrumentIdsToCheck));

            $instrumentStatuses = [];
            if (!empty($allInstrumentIdsToCheck)) {
                $placeholders = implode(',', array_fill(0, count($allInstrumentIdsToCheck), '?'));
                $stmtStatuses = $conn->prepare("SELECT instrument_id, status FROM instruments WHERE instrument_id IN ($placeholders)");
                if ($stmtStatuses === false) throw new Exception("Gagal mempersiapkan query status instrumen: " . $conn->error);
                
                $stmtStatuses->bind_param(str_repeat('i', count($allInstrumentIdsToCheck)), ...$allInstrumentIdsToCheck);
                $stmtStatuses->execute();
                $resultStatuses = $stmtStatuses->get_result();
                while($row = $resultStatuses->fetch_assoc()) {
                    $instrumentStatuses[$row['instrument_id']] = $row['status'];
                }
                $stmtStatuses->close();
            }

            foreach ($itemsFromDb as $item) {
                if ($item['item_type'] === 'instrument') {
                    $item['status'] = $instrumentStatuses[$item['item_id']] ?? 'rusak';
                } elseif ($item['item_type'] === 'set') {
                    $statusHierarchy = ['rusak' => 3, 'perbaikan' => 2, 'tersedia' => 1, 'default' => 0];
                    $worstStatus = 'default';
                    
                    if (!empty($item['item_snapshot'])) {
                        $snapshot = json_decode($item['item_snapshot'], true);
                        if (is_array($snapshot)) {
                            foreach ($snapshot as $instrumentInSet) {
                                $instrumentId = $instrumentInSet['instrument_id'];
                                $currentInstrumentStatus = $instrumentStatuses[$instrumentId] ?? 'rusak';
                                if (($statusHierarchy[$currentInstrumentStatus] ?? 0) > ($statusHierarchy[$worstStatus] ?? 0)) {
                                    $worstStatus = $currentInstrumentStatus;
                                }
                            }
                        }
                    }
                    $item['status'] = $worstStatus;
                }
                $loadData['items'][] = $item;
            }
            
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