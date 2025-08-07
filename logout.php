<?php
/**
 * Logout Script
 *
 * Destroys the current session and redirects the user to the login page.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Authentication
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

// Initialize the session.
// session_start() is usually called in config.php, but it's good practice
// to ensure it's started before manipulating session variables,
// especially if this script could be accessed independently.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all of the session variables.
$_SESSION = [];

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Set to a past time to expire the cookie
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
// Optionally, you can add a query parameter to show a "logged out successfully" message.
// e.g., header("Location: login.html?status=logged_out");
header("Location: login.php");
exit;
