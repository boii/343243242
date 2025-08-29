<?php
// /api/v1/instruments.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk daftar instrumen dengan paginasi.
 *
 * @param mysqli $conn Koneksi database.
 * @return void
 */
function handleGetInstrumentList(mysqli $conn): void
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 1, 'max_range' => 100]]);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT instrument_id, instrument_name, instrument_code, status 
            FROM instruments 
            ORDER BY instrument_name ASC 
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
 * Menangani permintaan GET untuk detail satu instrumen.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $instrumentId ID dari instrumen yang diminta.
 * @return void
 */
function handleGetInstrumentDetails(mysqli $conn, int $instrumentId): void
{
    $sql = "SELECT i.instrument_id, i.instrument_name, i.instrument_code, i.status, i.notes, it.type_name, d.department_name
            FROM instruments i
            LEFT JOIN instrument_types it ON i.instrument_type_id = it.type_id
            LEFT JOIN departments d ON i.department_id = d.department_id
            WHERE i.instrument_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $instrumentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($data = $result->fetch_assoc()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Instrumen tidak ditemukan.']);
    }
    $stmt->close();
}