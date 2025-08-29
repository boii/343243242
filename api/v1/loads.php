<?php
// /api/v1/loads.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk daftar muatan (loads).
 * @param mysqli $conn Koneksi database.
 */
function handleGetLoadList(mysqli $conn): void
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 1, 'max_range' => 100]]);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT sl.load_id, sl.load_name, sl.status, sl.created_at, m.machine_name
            FROM sterilization_loads sl
            LEFT JOIN machines m ON sl.machine_id = m.machine_id
            ORDER BY sl.created_at DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Menangani permintaan GET untuk detail satu muatan.
 * @param mysqli $conn Koneksi database.
 * @param int $loadId ID dari muatan yang diminta.
 */
function handleGetLoadDetails(mysqli $conn, int $loadId): void
{
    $stmtLoad = $conn->prepare("SELECT sl.*, m.machine_name FROM sterilization_loads sl LEFT JOIN machines m ON sl.machine_id = m.machine_id WHERE sl.load_id = ?");
    $stmtLoad->bind_param("i", $loadId);
    $stmtLoad->execute();
    $resultLoad = $stmtLoad->get_result();

    if ($loadData = $resultLoad->fetch_assoc()) {
        $stmtItems = $conn->prepare(
            "SELECT li.item_type, li.item_snapshot, CASE li.item_type WHEN 'instrument' THEN i.instrument_name ELSE s.set_name END as item_name
             FROM sterilization_load_items li
             LEFT JOIN instruments i ON li.item_type = 'instrument' AND li.item_id = i.instrument_id
             LEFT JOIN instrument_sets s ON li.item_type = 'set' AND li.item_id = s.set_id
             WHERE li.load_id = ?"
        );
        $stmtItems->bind_param("i", $loadId);
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();
        $loadData['items'] = $resultItems->fetch_all(MYSQLI_ASSOC);
        $stmtItems->close();

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $loadData]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Muatan tidak ditemukan.']);
    }
    $stmtLoad->close();
}

/**
 * Menangani permintaan POST untuk membuat muatan baru.
 * @param mysqli $conn Koneksi database.
 */
function handleCreateLoad(mysqli $conn): void
{
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $machineId = filter_var($input['machine_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$machineId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'machine_id (integer) wajib diisi.']);
        return;
    }

    $destinationDepartmentId = filter_var($input['destination_department_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $notes = trim($input['notes'] ?? '');
    $userId = $input['created_by_user_id'] ?? null;

    try {
        $datePart = date('dmy');
        $stmtSeq = $conn->prepare("SELECT COUNT(load_id) as daily_count FROM sterilization_loads WHERE DATE(created_at) = CURDATE()");
        $stmtSeq->execute();
        $nextSeq = (int) $stmtSeq->get_result()->fetch_assoc()['daily_count'] + 1;
        $loadName = sprintf("MUATAN-%s-%02d", $datePart, $nextSeq);
        $stmtSeq->close();

        $sql = "INSERT INTO sterilization_loads (load_name, created_by_user_id, machine_id, destination_department_id, notes, status) VALUES (?, ?, ?, ?, ?, 'persiapan')";
        $stmt = $conn->prepare($sql);
        $notesToInsert = !empty($notes) ? $notes : null;
        $stmt->bind_param("siiis", $loadName, $userId, $machineId, $destinationDepartmentId, $notesToInsert);

        if ($stmt->execute()) {
            $newLoadId = $stmt->insert_id;
            log_activity('API_CREATE_LOAD', $userId, "Muatan baru dibuat via API: " . $loadName, 'load', $newLoadId);

            $stmtGet = $conn->prepare("SELECT * FROM sterilization_loads WHERE load_id = ?");
            $stmtGet->bind_param("i", $newLoadId);
            $stmtGet->execute();
            $newLoadData = $stmtGet->get_result()->fetch_assoc();

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Muatan berhasil dibuat.', 'data' => $newLoadData]);
        } else {
            throw new Exception("Eksekusi database gagal: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Menangani permintaan POST untuk menambah item ke dalam muatan.
 * @param mysqli $conn Koneksi database.
 * @param int $loadId ID dari muatan target.
 */
function handleAddItemToLoad(mysqli $conn, int $loadId): void
{
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    // Validasi input dari body JSON
    $itemId = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT);
    $itemType = trim($input['item_type'] ?? '');
    if (!$itemId || !in_array($itemType, ['instrument', 'set'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'item_id (integer) dan item_type (string: "instrument" atau "set") wajib diisi.']);
        return;
    }

    try {
        // Pastikan muatan masih dalam status 'persiapan'
        $stmtCheck = $conn->prepare("SELECT status FROM sterilization_loads WHERE load_id = ?");
        $stmtCheck->bind_param("i", $loadId);
        $stmtCheck->execute();
        $load = $stmtCheck->get_result()->fetch_assoc();
        if (!$load) {
            http_response_code(404);
            throw new Exception('Muatan tidak ditemukan.');
        }
        if ($load['status'] !== 'persiapan') {
            http_response_code(409); // Conflict
            throw new Exception('Tidak dapat menambahkan item. Muatan tidak lagi dalam status persiapan.');
        }
        $stmtCheck->close();

        $itemSnapshotJson = null;
        if ($itemType === 'set') {
            $stmtSnapshot = $conn->prepare("SELECT instrument_id, quantity FROM instrument_set_items WHERE set_id = ?");
            $stmtSnapshot->bind_param("i", $itemId);
            $stmtSnapshot->execute();
            $snapshotItems = $stmtSnapshot->get_result()->fetch_all(MYSQLI_ASSOC);
            $itemSnapshotJson = json_encode($snapshotItems);
            $stmtSnapshot->close();
        }

        $sqlInsert = "INSERT INTO sterilization_load_items (load_id, item_id, item_type, item_snapshot) VALUES (?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("iiss", $loadId, $itemId, $itemType, $itemSnapshotJson);

        if ($stmtInsert->execute()) {
            http_response_code(201); // Created
            echo json_encode(['success' => true, 'message' => 'Item berhasil ditambahkan ke muatan.']);
        } else {
            throw new Exception('Gagal menambahkan item: ' . $stmtInsert->error);
        }
        $stmtInsert->close();

    } catch (Exception $e) {
        if (http_response_code() === 200) {
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}