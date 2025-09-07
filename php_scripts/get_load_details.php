<?php
/**
 * Get Sterilization Load Details (v2 - Patched for Set Status Indicator)
 *
 * Fetches comprehensive details for a specific sterilization load, including its items.
 * This patched version now correctly determines a "summary status" for sets
 * based on the status of their constituent instruments, allowing the frontend
 * to display the correct color indicator for every item in the load.
 *
 * PHP version 7.4 or higher
 *
 * @category         Backend
 * @package          Sterilabel
 * @author           Your Name <you@example.com>
 * @license          MIT License
 * @link             null
 */

declare(strict_types=1);

require_once '../config.php';
require_once '../app/helpers.php';

// Set header to JSON output
header('Content-Type: application/json');

// Get Load ID from the request
$loadId = filter_input(INPUT_GET, 'load_id', FILTER_VALIDATE_INT);

if (!$loadId) {
    echo json_encode(['success' => false, 'error' => 'Invalid Load ID provided.']);
    exit;
}

$conn = connectToDatabase();

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

try {
    // Main query to get load details
    $sql = "SELECT
                sl.load_id,
                sl.load_name,
                sl.notes,
                sl.status,
                sl.created_at,
                sl.cycle_id,
                u.full_name AS creator_name,
                m.machine_id,
                m.machine_name,
                d.department_id AS destination_department_id,
                d.department_name AS destination_department_name,
                sc.cycle_number
            FROM sterilization_loads sl
            LEFT JOIN users u ON sl.created_by_user_id = u.user_id
            LEFT JOIN machines m ON sl.machine_id = m.machine_id
            LEFT JOIN departments d ON sl.destination_department_id = d.department_id
            LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
            WHERE sl.load_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loadId);
    $stmt->execute();
    $result = $stmt->get_result();
    $load = $result->fetch_assoc();

    if (!$load) {
        throw new Exception('Load not found.');
    }

    // Fetch items in the load
    $sqlItems = "SELECT
                    li.load_item_id,
                    li.item_id,
                    li.item_type,
                    li.quantity,
                    li.item_snapshot,
                    CASE
                        WHEN li.item_type = 'instrument' THEN i.instrument_name
                        WHEN li.item_type = 'set' THEN s.set_name
                    END AS item_name,
                    CASE
                        WHEN li.item_type = 'instrument' THEN i.instrument_code
                        WHEN li.item_type = 'set' THEN s.set_code
                    END AS item_code,
                    -- For individual instruments, directly fetch their status
                    CASE
                        WHEN li.item_type = 'instrument' THEN i.status
                        ELSE NULL
                    END AS status,
                    -- Calculate predicted expiry days based on item or set specific expiry, then global default
                    COALESCE(
                        IF(li.item_type = 'instrument', i.expiry_in_days, s.expiry_in_days),
                        (SELECT setting_value FROM app_settings WHERE setting_name = 'default_expiry_days')
                    ) AS predicted_expiry_days
                FROM sterilization_load_items li
                LEFT JOIN instruments i ON li.item_id = i.instrument_id AND li.item_type = 'instrument'
                LEFT JOIN instrument_sets s ON li.item_id = s.set_id AND li.item_type = 'set'
                WHERE li.load_id = ?
                ORDER BY item_name ASC";

    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->bind_param("i", $loadId);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    $items = $resultItems->fetch_all(MYSQLI_ASSOC);

    // --- PATCH START: Determine Summary Status for Sets ---
    // Define status priority: lower number is higher priority
    $statusPriority = [
        'rusak' => 1,
        'perbaikan' => 2,
        'sterilisasi' => 3, // In case it's already marked as being processed
        'tersedia' => 4,
    ];

    // Prepare a statement to get statuses of all instruments in a set
    $sqlSetInstrumentStatus = "SELECT i.status
                               FROM instrument_set_items isi
                               JOIN instruments i ON isi.instrument_id = i.instrument_id
                               WHERE isi.set_id = ?";
    $stmtSetStatus = $conn->prepare($sqlSetInstrumentStatus);

    foreach ($items as &$item) { // Use reference to modify the array directly
        if ($item['item_type'] === 'set') {
            $stmtSetStatus->bind_param("i", $item['item_id']);
            $stmtSetStatus->execute();
            $resultStatus = $stmtSetStatus->get_result();

            $instrumentStatuses = [];
            while ($row = $resultStatus->fetch_assoc()) {
                $instrumentStatuses[] = $row['status'];
            }

            if (empty($instrumentStatuses)) {
                // If set is empty, consider it available
                $item['status'] = 'tersedia';
            } else {
                // Determine the highest priority status from the instruments in the set
                $highestPriorityStatus = 'tersedia';
                $minPriorityValue = $statusPriority['tersedia'];

                foreach ($instrumentStatuses as $status) {
                    $currentPriority = $statusPriority[$status] ?? 99;
                    if ($currentPriority < $minPriorityValue) {
                        $minPriorityValue = $currentPriority;
                        $highestPriorityStatus = $status;
                    }
                }
                $item['status'] = $highestPriorityStatus;
            }
        }
    }
    unset($item); // Unset reference to avoid side effects

    $stmtSetStatus->close();
    // --- PATCH END ---

    $load['items'] = $items;

    echo json_encode(['success' => true, 'load' => $load]);
} catch (Exception $e) {
    error_log("Error in get_load_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($stmtItems)) {
        $stmtItems->close();
    }
    if ($conn) {
        $conn->close();
    }
}
