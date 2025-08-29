<?php
// /api/v1/cycles.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk daftar siklus sterilisasi.
 *
 * @param mysqli $conn Koneksi database.
 * @return void
 */
function handleGetCycleList(mysqli $conn): void
{
    // Logika filter (bisa dikembangkan sesuai kebutuhan)
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 1, 'max_range' => 100]]);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT sc.cycle_id, sc.cycle_number, sc.machine_name, sc.cycle_date, sc.status, u.full_name as operator_name
            FROM sterilization_cycles sc
            LEFT JOIN users u ON sc.operator_user_id = u.user_id
            ORDER BY sc.cycle_date DESC
            LIMIT ? OFFSET ?";
    
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
 * Menangani permintaan GET untuk detail satu siklus.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $cycleId ID dari siklus yang diminta.
 * @return void
 */
function handleGetCycleDetails(mysqli $conn, int $cycleId): void
{
    // Ambil detail siklus utama
    $stmtCycle = $conn->prepare("SELECT sc.*, u.full_name as operator_name FROM sterilization_cycles sc LEFT JOIN users u ON sc.operator_user_id = u.user_id WHERE sc.cycle_id = ?");
    $stmtCycle->bind_param("i", $cycleId);
    $stmtCycle->execute();
    $resultCycle = $stmtCycle->get_result();

    if ($cycleData = $resultCycle->fetch_assoc()) {
        // Ambil muatan yang terkait dengan siklus ini
        $stmtLoads = $conn->prepare("SELECT load_id, load_name, status, created_at FROM sterilization_loads WHERE cycle_id = ?");
        $stmtLoads->bind_param("i", $cycleId);
        $stmtLoads->execute();
        $resultLoads = $stmtLoads->get_result();
        $loads = $resultLoads->fetch_all(MYSQLI_ASSOC);
        $stmtLoads->close();

        // --- PENAMBAHAN HATEOAS ---
        $cycleData['_links'] = [
            'self' => ['href' => "/api/v1/cycles/{$cycleId}"]
        ];
        
        // Tambahkan links untuk setiap item muatan
        foreach ($loads as &$load) {
            $load['_links'] = [
                'self' => ['href' => "/api/v1/loads/{$load['load_id']}"]
            ];
        }
        unset($load); // Hapus referensi
        
        $cycleData['loads'] = $loads;
        // --- AKHIR HATEOAS ---

        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $cycleData]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'fail', 'data' => ['cycle' => 'Siklus tidak ditemukan.']]);
    }
    $stmtCycle->close();
}