<?php
/**
 * Update Label Status Action Script
 *
 * Handles POST requests to update a label's status (e.g., mark as used,
 * validate/activate, report issue) and uses the standardized flash message system.
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

require_once '../config.php'; // Path ke config.php dari folder php_scripts

// --- Authorization Check: Hanya Pengguna yang Login ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak. Anda harus login untuk melakukan tindakan ini.'];
    header("Location: ../label_history.php"); // Redirect ke riwayat sebagai fallback
    exit;
}

// --- CSRF Token Check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid atau sesi mungkin telah berakhir. Silakan coba lagi.'];
    $redirectUid = $_POST['label_unique_id_for_redirect'] ?? ($_GET['uid'] ?? '');
    if (!empty($redirectUid)) {
        header("Location: ../verify_label.php?uid=" . urlencode($redirectUid));
    } else {
        header("Location: ../label_history.php");
    }
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$errorMessages = [];
$labelUniqueIdForRedirect = trim($_POST['label_unique_id_for_redirect'] ?? '');

if (empty($labelUniqueIdForRedirect)) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Aksi gagal: ID Label tidak ada untuk pengalihan.'];
    header("Location: ../label_history.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $recordIdToUpdate = filter_var($_POST['record_id_to_update'] ?? null, FILTER_VALIDATE_INT);
    $action = $_POST['action_type'] ?? null;
    $issueNote = trim($_POST['issue_note'] ?? '');
    $loggedInUserId = $_SESSION['user_id'] ?? null;
    
    $newStatus = null;
    $logActionType = null;
    $logDetails = '';
    $currentLabelStatusForAction = null;

    if ($recordIdToUpdate && $action && $loggedInUserId) {
        $connAction = connectToDatabase();
        if ($connAction) {
            // Ambil status dan notes saat ini sebelum update
            $sqlCheck = "SELECT status, notes FROM sterilization_records WHERE record_id = ?";
            if ($stmtCheck = $connAction->prepare($sqlCheck)) {
                $stmtCheck->bind_param("i", $recordIdToUpdate);
                $stmtCheck->execute();
                $resultCheck = $stmtCheck->get_result();
                if ($rowCheck = $resultCheck->fetch_assoc()) {
                    $currentLabelStatusForAction = $rowCheck['status'];
                    $existingNotes = $rowCheck['notes'];
                }
                $stmtCheck->close();
            } else {
                $errorMessages[] = "Gagal memeriksa status label saat ini: " . $connAction->error;
            }

            if ($currentLabelStatusForAction && empty($errorMessages)) {
                $sqlUpdate = null;
                $paramsForUpdate = [];
                $typesForUpdate = "";
                $flashMessage = [];

                if ($action === 'mark_as_used' && $currentLabelStatusForAction === 'active') {
                    $newStatus = 'used';
                    $logActionType = 'MARK_LABEL_USED';
                    $logDetails = "Label (UID: " . $labelUniqueIdForRedirect . ") ditandai sebagai 'Digunakan'.";
                    $sqlUpdate = "UPDATE sterilization_records SET status = ?, used_at = NOW() WHERE record_id = ?";
                    $paramsForUpdate = [$newStatus, $recordIdToUpdate];
                    $typesForUpdate = "si";
                    $flashMessage = ['type' => 'success', 'text' => "Label berhasil ditandai sebagai 'Digunakan'."];
                } elseif ($action === 'validate_activate' && $currentLabelStatusForAction === 'pending_validation') {
                    $newStatus = 'active';
                    $logActionType = 'VALIDATE_LABEL';
                    $logDetails = "Label (UID: " . $labelUniqueIdForRedirect . ") divalidasi dan diaktifkan.";
                    $sqlUpdate = "UPDATE sterilization_records SET status = ?, used_at = NULL, validated_by_user_id = ?, validated_at = NOW() WHERE record_id = ?";
                    $paramsForUpdate = [$newStatus, $loggedInUserId, $recordIdToUpdate];
                    $typesForUpdate = "sii";
                    $flashMessage = ['type' => 'success', 'text' => "Label berhasil divalidasi dan diaktifkan."];
                } elseif ($action === 'report_issue' && $currentLabelStatusForAction === 'pending_validation') {
                    $newStatus = 'recalled';
                    $logActionType = 'RECALL_LABEL';
                    $updatedNotes = $existingNotes;
                    if (!empty($issueNote)) {
                        $notePrefix = "Laporan Masalah oleh user ID {$loggedInUserId} (" . date('d-m-Y H:i') . "): ";
                        $updatedNotes = $notePrefix . $issueNote . "\n-----------------\n" . $existingNotes;
                        $logDetails = "Masalah dilaporkan untuk Label (UID: " . $labelUniqueIdForRedirect . ") dengan catatan: " . $issueNote;
                    } else {
                         $updatedNotes = "Masalah dilaporkan (tanpa catatan tambahan) oleh user ID {$loggedInUserId} (" . date('d-m-Y H:i') . ")\n-----------------\n" . $existingNotes;
                         $logDetails = "Masalah dilaporkan untuk Label (UID: " . $labelUniqueIdForRedirect . ") tanpa catatan tambahan.";
                    }
                    $sqlUpdate = "UPDATE sterilization_records SET status = ?, notes = ? WHERE record_id = ?";
                    $paramsForUpdate = [$newStatus, $updatedNotes, $recordIdToUpdate];
                    $typesForUpdate = "ssi";
                    $flashMessage = ['type' => 'success', 'text' => "Masalah pada label telah dilaporkan, status diubah menjadi 'Ditarik Kembali'."];
                } else {
                    $errorMessages[] = "Aksi tidak valid untuk status label saat ini ('" . htmlspecialchars($currentLabelStatusForAction) . "').";
                }

                if ($newStatus && $sqlUpdate && empty($errorMessages)) {
                    if ($stmtUpdate = $connAction->prepare($sqlUpdate)) {
                        $stmtUpdate->bind_param($typesForUpdate, ...$paramsForUpdate);
                        if ($stmtUpdate->execute()) {
                            if ($stmtUpdate->affected_rows > 0) {
                                // MODIFICATION: Use new flash message system
                                $_SESSION['flash_message'] = $flashMessage;
                                if ($logActionType) {
                                    log_activity($logActionType, $loggedInUserId, $logDetails, 'label', $recordIdToUpdate);
                                }
                            } else {
                                 // MODIFICATION: Use new flash message system
                                 $_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Tidak ada perubahan status pada label atau label tidak ditemukan.'];
                            }
                        } else {
                            $errorMessages[] = "Gagal memperbarui status label: " . $stmtUpdate->error;
                        }
                        $stmtUpdate->close();
                    } else {
                        $errorMessages[] = "Gagal mempersiapkan statement pembaruan: " . $connAction->error;
                    }
                }
            } elseif (empty($errorMessages)) {
                $errorMessages[] = "Label tidak ditemukan untuk aksi ini.";
            }
            $connAction->close();
        } else {
            if(empty($errorMessages)) $errorMessages[] = "Koneksi database gagal.";
        }
    } else {
        if(empty($errorMessages)) $errorMessages[] = "Data tidak lengkap untuk melakukan aksi atau pengguna tidak login.";
    }
} else {
    $errorMessages[] = "Permintaan tidak valid.";
}

if (!empty($errorMessages)) {
    // MODIFICATION: Use new flash message system
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => implode("<br>", $errorMessages)];
}

header("Location: ../verify_label.php?uid=" . urlencode($labelUniqueIdForRedirect));
exit;