<?php
// /api/utils.php

declare(strict_types=1);

/**
 * Memvalidasi Kunci API dan mengambil detailnya.
 * Jika valid, fungsi ini juga memperbarui waktu penggunaan terakhir.
 *
 * @param mysqli $conn Koneksi database yang aktif.
 * @param string $apiKey Kunci API yang diterima dari header permintaan.
 * @return array|null Mengembalikan array berisi detail kunci jika valid, atau null jika tidak.
 */
function getApiKeyDetails(mysqli $conn, string $apiKey): ?array
{
    if (empty($apiKey)) {
        return null;
    }

    // Cari kunci API di database
    $stmt = $conn->prepare("SELECT id, permissions, is_active FROM api_keys WHERE api_key = ? LIMIT 1");
    if (!$stmt) {
        error_log("API Auth Error: Failed to prepare statement.");
        return null;
    }

    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($keyData = $result->fetch_assoc()) {
        $stmt->close();

        // Periksa apakah kunci aktif
        if ((bool)$keyData['is_active']) {
            // Kunci valid dan aktif, perbarui last_used_at (opsional, baik untuk audit)
            $updateStmt = $conn->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("i", $keyData['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            return $keyData; // Kunci valid, kembalikan detailnya
        }
    } else {
         $stmt->close();
    }

    return null; // Kunci tidak ditemukan atau tidak aktif
}