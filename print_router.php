<?php
/**
 * Single Label Print Router (with Status Validation)
 *
 * Checks the application settings and includes the appropriate
 * single label print template (normal or half).
 * This version adds a security check to ensure only printable labels can be processed.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Routing
 * @package  Sterilabel
 * @author   Your Name
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once 'config.php'; // Memuat $app_settings

// Security check: Ensure the label is in a printable state before proceeding
$labelUniqueId = trim($_GET['label_uid'] ?? '');
if (empty($labelUniqueId)) {
    die("Error: ID Label tidak disediakan.");
}

$conn = connectToDatabase();
if ($conn) {
    $stmt = $conn->prepare("SELECT status FROM sterilization_records WHERE label_unique_id = ?");
    $stmt->bind_param("s", $labelUniqueId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($label = $result->fetch_assoc()) {
        if (!in_array($label['status'], ['active', 'used'])) {
            $stmt->close();
            $conn->close();
            die("Error: Label dengan status '" . htmlspecialchars($label['status']) . "' tidak dapat dicetak.");
        }
    } else {
        $stmt->close();
        $conn->close();
        die("Error: Label tidak ditemukan.");
    }
    $stmt->close();
    $conn->close();
} else {
    die("Error: Gagal terhubung ke database.");
}


// Ambil template yang dipilih dari pengaturan, default ke 'normal'
$template = $app_settings['print_template'] ?? 'normal';

if ($template === 'half') {
    // Muat template separuh halaman
    require 'print_thermal_half.php';
} else {
    // Muat template normal (default)
    require 'print_thermal.php';
}