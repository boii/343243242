<?php
// /api/v1/master_data.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk data master.
 *
 * @param mysqli $conn Koneksi database.
 * @param string $masterType Jenis data master (departments, machines, instrument-types).
 * @return void
 */
function handleGetMasterData(mysqli $conn, string $masterType): void
{
    $allowedTypes = [
        'departments' => ['table' => 'departments', 'order' => 'department_name'],
        'machines' => ['table' => 'machines', 'order' => 'machine_name'],
        'instrument-types' => ['table' => 'instrument_types', 'order' => 'type_name']
    ];

    if (!array_key_exists($masterType, $allowedTypes)) {
        http_response_code(404);
        echo json_encode(['status' => 'fail', 'data' => ['type' => 'Tipe master data tidak valid.']]);
        exit;
    }

    $table = $allowedTypes[$masterType]['table'];
    $order = $allowedTypes[$masterType]['order'];

    $result = $conn->query("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY {$order} ASC");
    $data = $result->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $data]);
}