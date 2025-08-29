<?php
// /api/utils.php

declare(strict_types=1);

/**
 * Memvalidasi Kunci API yang diberikan terhadap database.
 * Jika valid, fungsi ini juga memperbarui waktu penggunaan terakhir.
 *
 * @param mysqli $conn Koneksi database yang aktif.
 * @param string $apiKey Kunci API yang diterima dari header permintaan.
 * @return bool True jika kunci valid dan aktif, false jika tidak.
 */
function validateApiKey(mysqli $conn, string $apiKey): bool
{
    if (empty($apiKey)) {
        return false;
    }

    // Cari kunci API di database
    $stmt = $conn->prepare("SELECT id, is_active FROM api_keys WHERE api_key = ? LIMIT 1");
    if (!$stmt) {
        error_log("API Auth Error: Failed to prepare statement.");
        return false;
    }

    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($keyData = $result->fetch_assoc()) {
        $stmt->close();

        // Periksa apakah kunci aktif
        if ($keyData['is_active']) {
            // Kunci valid dan aktif, perbarui last_used_at (opsional, baik untuk audit)
            $updateStmt = $conn->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("i", $keyData['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
            return true;
        }
    } else {
         $stmt->close();
    }

    return false; // Kunci tidak ditemukan atau tidak aktif
}