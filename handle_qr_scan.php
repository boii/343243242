<?php
/**
 * QR Code Scan Handler
 *
 * This script receives the scanned QR code's UID.
 * It checks the user's login status and redirects to the appropriate
 * label detail page (public or authenticated).
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Routing
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once 'config.php'; // For session_start() and other potential config needs

$uid = trim($_GET['uid'] ?? '');

if (empty($uid)) {
    // Jika tidak ada UID, mungkin arahkan ke halaman error atau index
    header("Location: index.php?error=invalid_qr_code");
    exit;
}

// Periksa status login dari sesi
$isUserLoggedIn = (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true);

if ($isUserLoggedIn) {
    // Pengguna sudah login, arahkan ke halaman verifikasi internal
    header("Location: verify_label.php?uid=" . urlencode($uid));
    exit;
} else {
    // Pengguna tidak login, arahkan ke halaman detail publik
    header("Location: public_label_view.php?uid=" . urlencode($uid));
    exit;
}
