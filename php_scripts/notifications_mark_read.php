<?php
/**
 * Mark User Notifications as Read (AJAX Endpoint)
 *
 * This script updates the `is_read` flag for all of a user's
 * unread notifications in the `user_notifications` table.
 * It is called via AJAX when the user clicks the notification bell.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category BackendProcessing
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

require_once '../config.php';

header('Content-Type: application/json');

// 1. Authorization: User must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

// 2. Security: Must be a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Metode permintaan tidak valid.']);
    exit;
}

// 3. CSRF Protection
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$csrfToken = $input['csrf_token'] ?? '';

if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF tidak valid.']);
    exit;
}

$loggedInUserId = $_SESSION['user_id'];
$response = ['success' => false];

$conn = connectToDatabase();
if ($conn) {
    // 4. Update the database
    // Set is_read = 1 for all unread (is_read = 0) notifications for this user.
    $sql = "UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $loggedInUserId);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['updated_rows'] = $stmt->affected_rows;
        } else {
            $response['error'] = 'Gagal mengeksekusi pembaruan.';
            error_log("Notification Mark Read Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $response['error'] = 'Gagal mempersiapkan statement.';
        error_log("Notification Mark Read Error: " . $conn->error);
    }
    $conn->close();
} else {
    $response['error'] = 'Koneksi database gagal.';
    http_response_code(500);
}

echo json_encode($response);
exit;