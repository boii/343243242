<?php
// /api/v1/users.php

declare(strict_types=1);

/**
 * Menangani permintaan GET untuk daftar pengguna.
 *
 * @param mysqli $conn Koneksi database.
 * @return void
 */
function handleGetUserList(mysqli $conn): void
{
    // Di dunia nyata, Anda mungkin ingin membatasi akses ke endpoint ini
    // berdasarkan izin Kunci API.
    $sql = "SELECT user_id, username, full_name, role FROM users ORDER BY full_name ASC";
    $result = $conn->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Menangani permintaan GET untuk detail satu pengguna.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $userId ID pengguna yang diminta.
 * @return void
 */
function handleGetUserDetails(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("SELECT user_id, username, full_name, email, role, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($data = $result->fetch_assoc()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pengguna tidak ditemukan.']);
    }
    $stmt->close();
}