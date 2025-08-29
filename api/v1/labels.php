<?php
// /api/v1/labels.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk detail label.
 *
 * @param mysqli $conn Koneksi database.
 * @param string $labelUid ID unik label yang diminta.
 * @return void
 */
function handleGetLabelDetails(mysqli $conn, string $labelUid): void
{
    if (empty($labelUid)) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'fail', 'data' => ['uid' => 'ID Label (UID) tidak boleh kosong.']]);
        exit;
    }

    // Otomatis perbarui status jika sudah kedaluwarsa
    $stmtUpdate = $conn->prepare("UPDATE sterilization_records SET status = 'expired' WHERE (status = 'active' OR status = 'pending_validation') AND expiry_date <= NOW() AND label_unique_id = ?");
    $stmtUpdate->bind_param("s", $labelUid);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Query komprehensif yang sama dengan di halaman verifikasi
    $sql = "SELECT 
                sr.label_unique_id, 
                sr.label_title,
                sr.item_type,
                sr.load_id, 
                sl.cycle_id,
                CASE sr.item_type 
                    WHEN 'instrument' THEN i.instrument_name 
                    WHEN 'set' THEN s.set_name 
                    ELSE 'N/A' 
                END as item_name,
                sr.status, 
                sr.created_at, 
                sr.expiry_date, 
                sr.used_at,
                sl.load_name,
                sc.cycle_number, 
                sc.machine_name,
                sc.cycle_date
            FROM sterilization_records sr
            LEFT JOIN instruments i ON sr.item_type = 'instrument' AND sr.item_id = i.instrument_id
            LEFT JOIN instrument_sets s ON sr.item_type = 'set' AND sr.item_id = s.set_id
            LEFT JOIN sterilization_loads sl ON sr.load_id = sl.load_id
            LEFT JOIN sterilization_cycles sc ON sl.cycle_id = sc.cycle_id
            WHERE sr.label_unique_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        error_log("API Error (get_label_details): Failed to prepare statement - " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
        exit;
    }

    $stmt->bind_param("s", $labelUid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($data = $result->fetch_assoc()) {
        // Menggunakan helper function dari config.php untuk mendapatkan teks status yang konsisten
        $statusInfo = getUniversalStatusBadge($data['status']);
        $data['status_display'] = $statusInfo['text'];

        // --- PENAMBAHAN HATEOAS ---
        $data['_links'] = [
            'self' => ['href' => "/api/v1/labels/{$labelUid}"]
        ];
        if (!empty($data['load_id'])) {
            $data['_links']['load'] = ['href' => "/api/v1/loads/{$data['load_id']}"];
        }
        if (!empty($data['cycle_id'])) {
            $data['_links']['cycle'] = ['href' => "/api/v1/cycles/{$data['cycle_id']}"];
        }
        if ($data['status'] === 'active') {
            $data['_links']['mark_used'] = [
                'href' => "/api/v1/labels/{$labelUid}/mark-used",
                'method' => 'POST'
            ];
        }
        // --- AKHIR HATEOAS ---

        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'fail', 'data' => ['uid' => 'Label dengan ID tersebut tidak ditemukan.']]);
    }

    $stmt->close();
}

/**
 * Menangani permintaan POST untuk menandai label sebagai "telah digunakan".
 *
 * @param mysqli $conn Koneksi database.
 * @param string $labelUid ID unik label yang akan diupdate.
 * @return void
 */
function handleMarkLabelUsed(mysqli $conn, string $labelUid): void
{
    // Ambil data dari body permintaan JSON
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true) ?? [];
    $userNote = trim($input['note'] ?? '');

    try {
        $conn->begin_transaction();

        $stmtGet = $conn->prepare("SELECT record_id, status, notes FROM sterilization_records WHERE label_unique_id = ? LIMIT 1 FOR UPDATE");
        $stmtGet->bind_param("s", $labelUid);
        $stmtGet->execute();
        $result = $stmtGet->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            throw new Exception("Label tidak ditemukan.");
        }

        $label = $result->fetch_assoc();
        $recordId = $label['record_id'];
        $existingNotes = $label['notes'];

        if ($label['status'] !== 'active') {
            http_response_code(409); // Conflict
            throw new Exception("Item ini sudah tidak aktif dan tidak bisa diubah statusnya.");
        }

        // Menangkap detail teknis (IP address klien yang memanggil API)
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown API Client';
        $userAgent = 'API Request'; // User agent tidak relevan untuk API

        // Format catatan baru
        $publicNote = "USED (via API): " . date('d-m-Y H:i');
        if (!empty($userNote)) {
            $publicNote .= " - Note: " . htmlspecialchars($userNote);
        }
        $updatedNotes = $publicNote . "\n-----------------\n" . $existingNotes;

        $stmtUpdate = $conn->prepare("UPDATE sterilization_records SET status = 'used', used_at = NOW(), notes = ?, action_ip_address = ?, action_user_agent = ? WHERE record_id = ?");
        $stmtUpdate->bind_param("sssi", $updatedNotes, $ipAddress, $userAgent, $recordId);

        if (!$stmtUpdate->execute()) {
            throw new Exception("Gagal memperbarui status item.");
        }

        $logDetails = "Label (UID: " . htmlspecialchars($labelUid) . ") ditandai 'Digunakan' via API.";
        if (!empty($userNote)) {
            $logDetails .= " Catatan: " . htmlspecialchars($userNote);
        }
        log_activity('API_MARK_USED', null, $logDetails, 'label', $recordId);

        $conn->commit();

        http_response_code(200); // OK
        echo json_encode(['status' => 'success', 'data' => ['message' => 'Status item berhasil diperbarui menjadi "Telah Digunakan".']]);

    } catch (Exception $e) {
        if ($conn->inTransaction) {
            $conn->rollback();
        }
        // Kode status HTTP sudah diatur di dalam blok try
        if (http_response_code() === 200) {
            http_response_code(500); // Default ke Internal Server Error jika belum diatur
        }
        
        // Sesuaikan dengan JSend
        if(http_response_code() >= 500){
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } else {
            echo json_encode(['status' => 'fail', 'data' => ['general' => $e->getMessage()]]);
        }
        
    } finally {
        if (isset($stmtGet)) $stmtGet->close();
        if (isset($stmtUpdate)) $stmtUpdate->close();
    }
}