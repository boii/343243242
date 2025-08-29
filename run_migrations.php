<?php
// /run_migrations.php

declare(strict_types=1);

require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Database Migrations</title><style>body { font-family: sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style></head><body>";
echo "<h1>Running Database Migrations...</h1>";

$conn = connectToDatabase();

if (!$conn) {
    echo "<p class='error'>FATAL: Could not connect to the database.</p>";
    exit;
}

// Migrasi 1: Buat tabel api_keys
$sql_create_api_keys_table = "
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_name` VARCHAR(100) NOT NULL COMMENT 'Nama sistem/klien yang menggunakan kunci ini',
  `api_key` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Kunci API yang sebenarnya',
  `permissions` VARCHAR(255) DEFAULT 'read_only' COMMENT 'Hak akses (contoh: read_only, read_write)',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_create_api_keys_table) === TRUE) {
    echo "<p class='success'>Migration successful: `api_keys` table created or already exists.</p>";
} else {
    echo "<p class='error'>Error creating `api_keys` table: " . $conn->error . "</p>";
}

// Migrasi 2: Masukkan contoh API Key untuk SIRS (hanya jika tabel kosong)
$result = $conn->query("SELECT id FROM `api_keys` LIMIT 1");
if ($result->num_rows == 0) {
    // Hasilkan kunci acak yang aman
    $sample_api_key = bin2hex(random_bytes(32));
    $client_name = 'Sistem Informasi Rumah Sakit Utama';

    $stmt = $conn->prepare("INSERT INTO `api_keys` (client_name, api_key, permissions) VALUES (?, ?, 'read_write')");
    $stmt->bind_param("ss", $client_name, $sample_api_key);

    if ($stmt->execute()) {
        echo "<p class='success'>Sample data inserted successfully.</p>";
        echo "<p><b>PENTING:</b> Simpan API Key ini di tempat yang aman. Anda akan menggunakannya untuk mengintegrasikan SIRS.</p>";
        echo "<p><b>Client:</b> " . htmlspecialchars($client_name) . "</p>";
        echo "<p><b>API Key:</b> <code>" . htmlspecialchars($sample_api_key) . "</code></p>";
    } else {
        echo "<p class='error'>Error inserting sample data: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p>Sample API key already exists. Skipping insertion.</p>";
}


$conn->close();
echo "</body></html>";

?>