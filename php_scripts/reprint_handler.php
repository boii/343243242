<?php
/**
 * Reprint Label Handler
 *
 * This script increments the print_count for a single label and then
 * forwards the necessary data to the print_multiple_labels.php page
 * to render the reprinted label with a watermark.
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

// Authorization & CSRF Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die("Akses ditolak.");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Akses tidak sah.");
}

$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

if (!$recordId) {
    die("ID record tidak valid.");
}

// Increment the print count in the database
$conn = connectToDatabase();
if ($conn) {
    $stmt = $conn->prepare("UPDATE sterilization_records SET print_count = print_count + 1 WHERE record_id = ?");
    $stmt->bind_param("i", $recordId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Forward to the print page using a self-submitting form
// This maintains the POST request method expected by print_multiple_labels.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mempersiapkan Cetak Ulang...</title>
</head>
<body onload="document.getElementById('reprintForm').submit();">
    <p>Harap tunggu, sedang mempersiapkan label untuk dicetak ulang...</p>
    <form id="reprintForm" action="../print_multiple_labels.php" method="POST" target="_blank">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_POST['csrf_token']); ?>">
        <input type="hidden" name="record_ids[]" value="<?php echo htmlspecialchars((string)$recordId); ?>">
    </form>
    <script>
        // Menutup tab ini setelah form disubmit
        setTimeout(function() { window.close(); }, 1000);
    </script>
</body>
</html>