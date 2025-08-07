<?php
/**
 * Multiple Labels Print Router
 *
 * Checks the application settings and includes the appropriate
 * multiple label print template (normal or half).
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

// Ambil template yang dipilih dari pengaturan, default ke 'normal'
$template = $app_settings['print_template'] ?? 'normal';

if ($template === 'half') {
    // Muat template separuh halaman untuk cetak massal
    require 'print_multiple_labels_half.php';
} else {
    // Muat template normal (default) untuk cetak massal
    require 'print_multiple_labels.php';
}