<?php
// /api/v1/process.php

declare(strict_types=1);

/**
 * Menangani permintaan POST untuk memproses muatan.
 * Ini membuat siklus baru, mengaitkannya dengan muatan, dan menyelesaikan keduanya.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $loadId ID muatan yang akan diproses.
 * @return void
 */
function handleProcessLoad(mysqli $conn, int $loadId): void
{
    // Ambil data dari body permintaan JSON (opsional, bisa dikembangkan)
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];
    $operatorUserId = filter_var($input['operator_user_id'] ?? null, FILTER_VALIDATE_INT); // Ambil operator_id dari request

    if (!$operatorUserId) {
        http_response_code(400);
        echo json_encode(['status' => 'fail', 'data' => ['operator_user_id' => 'operator_user_id (integer) wajib diisi dalam body request.']]);
        return;
    }

    $conn->begin_transaction();
    try {
        // 1. Ambil detail muatan, pastikan statusnya 'persiapan'
        $stmtGetLoad = $conn->prepare("SELECT load_name, machine_id FROM sterilization_loads WHERE load_id = ? AND status = 'persiapan' FOR UPDATE");
        $stmtGetLoad->bind_param("i", $loadId);
        $stmtGetLoad->execute();
        $resultLoad = $stmtGetLoad->get_result();
        if (!($load = $resultLoad->fetch_assoc())) {
            http_response_code(409); // Conflict
            throw new Exception("Muatan tidak ditemukan atau statusnya bukan 'Persiapan'.");
        }
        $machineId = $load['machine_id'];
        $stmtGetLoad->close();

        if (!$machineId) {
            throw new Exception("Informasi mesin tidak ditemukan pada muatan ini.");
        }

        // 2. Buat siklus baru
        $stmtGetMachine = $conn->prepare("SELECT machine_name, machine_code FROM machines WHERE machine_id = ?");
        $stmtGetMachine->bind_param("i", $machineId);
        $stmtGetMachine->execute();
        $machine = $stmtGetMachine->get_result()->fetch_assoc();
        $machineName = $machine['machine_name'];
        $machineCode = $machine['machine_code'];
        $stmtGetMachine->close();

        $datePart = date('dmy');
        $likePattern = "SIKLUS-" . $machineCode . '-' . $datePart . '-%';
        $stmtGetSeq = $conn->prepare("SELECT COUNT(cycle_id) as daily_count FROM sterilization_cycles WHERE cycle_number LIKE ?");
        $stmtGetSeq->bind_param("s", $likePattern);
        $stmtGetSeq->execute();
        $dailyCount = (int) $stmtGetSeq->get_result()->fetch_assoc()['daily_count'];
        $nextSeq = $dailyCount + 1;
        $cycleNumber = sprintf("SIKLUS-%s-%s-%02d", $machineCode, $datePart, $nextSeq);
        $stmtGetSeq->close();

        $sqlCreateCycle = "INSERT INTO sterilization_cycles (machine_name, cycle_number, cycle_date, operator_user_id, status) VALUES (?, ?, NOW(), ?, 'completed')";
        $stmtCycle = $conn->prepare($sqlCreateCycle);
        $stmtCycle->bind_param("ssi", $machineName, $cycleNumber, $operatorUserId);
        if (!$stmtCycle->execute()) {
            throw new Exception("Gagal membuat data siklus baru: " . $stmtCycle->error);
        }
        $newCycleId = $stmtCycle->insert_id;
        $stmtCycle->close();

        // 3. Update muatan dengan cycle_id baru dan status 'selesai'
        $sqlUpdateLoad = $conn->prepare("UPDATE sterilization_loads SET cycle_id = ?, status = 'selesai' WHERE load_id = ?");
        $sqlUpdateLoad->bind_param("ii", $newCycleId, $loadId);
        if (!$sqlUpdateLoad->execute()) {
            throw new Exception("Gagal memperbarui status muatan: " . $sqlUpdateLoad->error);
        }
        $sqlUpdateLoad->close();

        // 4. Ambil data siklus yang baru dibuat untuk respons
        $stmtGetResponse = $conn->prepare("SELECT * FROM sterilization_cycles WHERE cycle_id = ?");
        $stmtGetResponse->bind_param("i", $newCycleId);
        $stmtGetResponse->execute();
        $newCycleData = $stmtGetResponse->get_result()->fetch_assoc();
        $stmtGetResponse->close();

        $conn->commit();

        log_activity('API_PROCESS_LOAD', $operatorUserId, "Muatan ID: $loadId diproses via API, membuat Siklus ID: $newCycleId.", 'load', $loadId);

        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $newCycleData]);

    } catch (Exception $e) {
        $conn->rollback();
        if (http_response_code() === 200) http_response_code(500); // Default error jika belum diatur
        
        if(http_response_code() >= 500){
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } else {
            echo json_encode(['status' => 'fail', 'data' => ['general' => $e->getMessage()]]);
        }
    }
}

/**
 * Menangani permintaan POST untuk membuat semua label untuk muatan.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $loadId ID muatan.
 * @return void
 */
function handleGenerateLabels(mysqli $conn, int $loadId): void
{
    global $app_settings;
    $globalDefaultExpiryDays = (int)($app_settings['default_expiry_days'] ?? 30);
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];
    $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['status' => 'fail', 'data' => ['user_id' => 'user_id (integer) pembuat label wajib diisi dalam body request.']]);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmtLoadInfo = $conn->prepare("SELECT cycle_id, destination_department_id FROM sterilization_loads WHERE load_id = ? AND status = 'selesai'");
        $stmtLoadInfo->bind_param("i", $loadId);
        $stmtLoadInfo->execute();
        $resultLoadInfo = $stmtLoadInfo->get_result();
        if (!($loadInfo = $resultLoadInfo->fetch_assoc())) {
            http_response_code(409); // Conflict
            throw new Exception("Label tidak dapat dibuat. Muatan tidak ditemukan atau statusnya bukan 'Selesai'.");
        }
        $cycleId = $loadInfo['cycle_id'];
        $destinationDepartmentId = $loadInfo['destination_department_id'];
        $stmtLoadInfo->close();

        $stmtItems = $conn->prepare("SELECT item_id, item_type, item_snapshot FROM sterilization_load_items WHERE load_id = ?");
        $stmtItems->bind_param("i", $loadId);
        $stmtItems->execute();
        $itemsToProcess = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtItems->close();

        if (empty($itemsToProcess)) {
            throw new Exception("Tidak ada item di dalam muatan ini untuk dibuatkan label.");
        }

        $instrumentIds = [];
        $setIds = [];
        foreach ($itemsToProcess as $item) {
            if ($item['item_type'] === 'set') {
                 $setIds[] = $item['item_id'];
                 if (!empty($item['item_snapshot'])) {
                    $snapshot = json_decode($item['item_snapshot'], true);
                    if (is_array($snapshot)) {
                        $instrumentIds = array_merge($instrumentIds, array_column($snapshot, 'instrument_id'));
                    }
                 }
            } else {
                $instrumentIds[] = $item['item_id'];
            }
        }
        $instrumentIds = array_unique(array_filter($instrumentIds));
        $setIds = array_unique(array_filter($setIds));

        $masterData = ['instrument' => [], 'set' => []];
        if (!empty($instrumentIds)) {
            $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
            $stmtMasterInst = $conn->prepare("SELECT instrument_id, instrument_name, expiry_in_days FROM instruments WHERE instrument_id IN ($placeholders)");
            $stmtMasterInst->bind_param(str_repeat('i', count($instrumentIds)), ...$instrumentIds);
            $stmtMasterInst->execute();
            $resInst = $stmtMasterInst->get_result();
            while ($row = $resInst->fetch_assoc()) $masterData['instrument'][$row['instrument_id']] = $row;
            $stmtMasterInst->close();
        }
        if (!empty($setIds)) {
            $placeholders = implode(',', array_fill(0, count($setIds), '?'));
            $stmtMasterSet = $conn->prepare("SELECT set_id, set_name, expiry_in_days FROM instrument_sets WHERE set_id IN ($placeholders)");
            $stmtMasterSet->bind_param(str_repeat('i', count($setIds)), ...$setIds);
            $stmtMasterSet->execute();
            $resSet = $stmtMasterSet->get_result();
            while ($row = $resSet->fetch_assoc()) $masterData['set'][$row['set_id']] = $row;
            $stmtMasterSet->close();
        }
        
        $sqlInsertLabel = "INSERT INTO sterilization_records (label_unique_id, load_id, cycle_id, item_id, item_type, label_title, created_by_user_id, expiry_date, status, label_items_snapshot, print_status, destination_department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, 'pending', ?)";
        $stmtInsert = $conn->prepare($sqlInsertLabel);
        
        $generatedUids = [];
        
        foreach ($itemsToProcess as $item) {
            $itemId = (int)$item['item_id'];
            $itemType = $item['item_type'];
            $itemSnapshotJson = $item['item_snapshot'] ?? null;
            
            $itemMaster = $masterData[$itemType][$itemId] ?? null;
            if (!$itemMaster) continue;
            
            $labelTitle = $itemMaster[$itemType . '_name'] ?? 'Item tidak dikenal';
            $daysUntilExpiry = $globalDefaultExpiryDays;

            if ($itemType === 'instrument') {
                $daysUntilExpiry = (int)($itemMaster['expiry_in_days'] ?? $globalDefaultExpiryDays);
            } elseif ($itemType === 'set') {
                if (isset($itemMaster['expiry_in_days']) && (int)$itemMaster['expiry_in_days'] > 0) {
                    $daysUntilExpiry = (int)$itemMaster['expiry_in_days'];
                } else {
                    if (!empty($itemSnapshotJson)) {
                        $snapshot = json_decode($itemSnapshotJson, true);
                        if (is_array($snapshot) && !empty($snapshot)) {
                            $expiryValues = [];
                            foreach ($snapshot as $snapItem) {
                                $instrumentInSet = $masterData['instrument'][(int)$snapItem['instrument_id']] ?? null;
                                $expiryValues[] = (int)($instrumentInSet['expiry_in_days'] ?? $globalDefaultExpiryDays);
                            }
                            if (!empty($expiryValues)) {
                                $daysUntilExpiry = min($expiryValues);
                            }
                        }
                    }
                }
            }
            
            $expiryDate = (new DateTime())->modify("+" . $daysUntilExpiry . " days")->format('Y-m-d H:i:s');
            
            $maxAttempts = 5;
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $labelUniqueId = strtoupper(bin2hex(random_bytes(4)));
                
                $stmtInsert->bind_param("siiississi", $labelUniqueId, $loadId, $cycleId, $itemId, $itemType, $labelTitle, $userId, $expiryDate, $itemSnapshotJson, $destinationDepartmentId);
                
                if ($stmtInsert->execute()) {
                    $generatedUids[] = $labelUniqueId;
                    break; 
                } else {
                    if ($conn->errno !== 1062) {
                        throw new Exception("Gagal membuat label untuk item ID {$itemId}: " . $stmtInsert->error);
                    }
                    if ($attempt === $maxAttempts - 1) {
                         throw new Exception("Gagal menghasilkan ID label unik setelah $maxAttempts percobaan.");
                    }
                }
            }
        }
        $stmtInsert->close();

        $conn->commit();
        
        log_activity('API_GENERATE_LABELS', $userId, "Membuat " . count($generatedUids) . " label untuk Muatan ID: $loadId via API.", 'load', $loadId);

        http_response_code(201); // Created
        echo json_encode([
            'status' => 'success',
            'data' => [
                'load_id' => $loadId,
                'labels_generated' => count($generatedUids),
                'label_uids' => $generatedUids
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        if (http_response_code() === 200) http_response_code(500);

        if(http_response_code() >= 500){
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } else {
            echo json_encode(['status' => 'fail', 'data' => ['general' => $e->getMessage()]]);
        }
    }
}