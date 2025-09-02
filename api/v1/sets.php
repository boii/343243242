<?php
// /api/v1/sets.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk daftar set instrumen.
 *
 * @param mysqli $conn Koneksi database.
 * @return void
 */
function handleGetSetList(mysqli $conn): void
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 1, 'max_range' => 100]]);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT set_id, set_name, set_code FROM instrument_sets ORDER BY set_name ASC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $data]);
}

/**
 * Menangani permintaan GET untuk detail satu set instrumen.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $setId ID dari set yang diminta.
 * @return void
 */
function handleGetSetDetails(mysqli $conn, int $setId): void
{
    // Ambil detail set
    $stmtSet = $conn->prepare("SELECT set_id, set_name, set_code, special_instructions FROM instrument_sets WHERE set_id = ?");
    $stmtSet->bind_param("i", $setId);
    $stmtSet->execute();
    $resultSet = $stmtSet->get_result();

    if ($setData = $resultSet->fetch_assoc()) {
        // Ambil isi instrumen dari set
        $stmtItems = $conn->prepare(
            "SELECT i.instrument_id, i.instrument_name, i.instrument_code, isi.quantity
             FROM instrument_set_items isi
             JOIN instruments i ON isi.instrument_id = i.instrument_id
             WHERE isi.set_id = ?"
        );
        $stmtItems->bind_param("i", $setId);
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();
        $instruments = $resultItems->fetch_all(MYSQLI_ASSOC);
        $stmtItems->close();

        // --- PENAMBAHAN HATEOAS ---
        $setData['_links'] = [
            'self' => ['href' => "/api/v1/sets/{$setId}"]
        ];
        
        foreach ($instruments as &$instrument) {
            $instrument['_links'] = [
                'self' => ['href' => "/api/v1/instruments/{$instrument['instrument_id']}"]
            ];
        }
        unset($instrument);

        $setData['instruments'] = $instruments;
        // --- AKHIR HATEOAS ---

        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $setData]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'fail', 'data' => ['set' => 'Set tidak ditemukan.']]);
    }
    $stmtSet->close();
}