<?php
/**
 * Process Instrument CSV Import (with Enhanced & Concise Error Reporting)
 */
declare(strict_types=1);

require_once '../config.php';

// Authorization & CSRF Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Akses ditolak.'];
    header("Location: ../manage_instruments.php");
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Token CSRF tidak valid.'];
    header("Location: ../manage_instruments.php");
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Gagal mengunggah file atau tidak ada file yang dipilih.'];
    header("Location: ../manage_instruments.php");
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$loggedInUserId = $_SESSION['user_id'] ?? null;

$successCount = 0;
$errorCount = 0;
$processedCodes = [];
$processedNames = [];

// Mengelompokkan detail error
$errorDetails = [
    'missing_data' => [],
    'duplicate_name_db' => [],
    'duplicate_code_db' => [],
    'invalid_type' => [],
    'invalid_department' => [],
    'db_error' => []
];

$conn = connectToDatabase();
if (!$conn) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Koneksi database gagal.'];
    header("Location: ../manage_instruments.php");
    exit;
}

// Pre-fetch master data and existing instrument names/codes for matching
$typesMap = [];
$deptsMap = [];
$existingNamesMap = [];
$existingCodesMap = [];
if ($result = $conn->query("SELECT type_id, LOWER(type_name) as name FROM instrument_types")) {
    while ($row = $result->fetch_assoc()) $typesMap[$row['name']] = $row['type_id'];
}
if ($result = $conn->query("SELECT department_id, LOWER(department_name) as name FROM departments")) {
    while ($row = $result->fetch_assoc()) $deptsMap[$row['name']] = $row['department_id'];
}
if ($result = $conn->query("SELECT LOWER(instrument_name) as name, instrument_code as code FROM instruments")) {
    while ($row = $result->fetch_assoc()) {
        $existingNamesMap[$row['name']] = true;
        if (!empty($row['code'])) {
            $existingCodesMap[strtoupper($row['code'])] = true;
        }
    }
}

$conn->begin_transaction();

try {
    if (($handle = fopen($file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        $rowNum = 1;

        $stmtInsert = $conn->prepare("INSERT INTO instruments (instrument_name, instrument_code, instrument_type_id, department_id, expiry_in_days, notes, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNum++;
            if (count(array_filter($data)) == 0) continue;
            
            if (count($header) != count($data)) {
                 $errorCount++; $errorDetails['missing_data'][] = "Baris {$rowNum}"; continue;
            }
            $row = array_combine($header, $data);

            $instrumentName = trim($row['instrument_name']);
            $instrumentCode = trim($row['instrument_code'] ?? '');
            
            if (empty($instrumentName) || empty($row['type_name']) || empty($row['department_name'])) {
                $errorCount++; $errorDetails['missing_data'][] = "Baris {$rowNum}"; continue;
            }

            $lowerInstrumentName = strtolower($instrumentName);
            if (isset($existingNamesMap[$lowerInstrumentName]) || isset($processedNames[$lowerInstrumentName])) {
                $errorCount++; $errorDetails['duplicate_name_db'][] = "Baris {$rowNum}"; continue;
            }
            $processedNames[$lowerInstrumentName] = true;

            if (empty($instrumentCode)) {
                $instrumentCode = "INST-" . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
            }

            $upperInstrumentCode = strtoupper($instrumentCode);
            if (isset($existingCodesMap[$upperInstrumentCode]) || isset($processedCodes[$upperInstrumentCode])) {
                $errorCount++; $errorDetails['duplicate_code_db'][] = "Baris {$rowNum}"; continue;
            }
            $processedCodes[$upperInstrumentCode] = true;

            $typeId = $typesMap[strtolower(trim($row['type_name']))] ?? null;
            $deptId = $deptsMap[strtolower(trim($row['department_name']))] ?? null;

            if (!$typeId) { $errorCount++; $errorDetails['invalid_type'][] = "Baris {$rowNum}"; continue; }
            if (!$deptId) { $errorCount++; $errorDetails['invalid_department'][] = "Baris {$rowNum}"; continue; }
            
            $stmtCheckCodeDB = $conn->prepare("SELECT instrument_id FROM instruments WHERE instrument_code = ?");
            $stmtCheckCodeDB->bind_param("s", $instrumentCode);
            $stmtCheckCodeDB->execute();
            if ($stmtCheckCodeDB->get_result()->num_rows > 0) {
                $errorCount++; $errorDetails['duplicate_code_db'][] = "Baris {$rowNum}"; $stmtCheckCodeDB->close(); continue;
            }
            $stmtCheckCodeDB->close();
            
            $expiry = !empty($row['expiry_in_days']) && is_numeric($row['expiry_in_days']) ? (int)$row['expiry_in_days'] : null;
            $notes = $row['notes'] ?? null;

            $stmtInsert->bind_param("ssiiisi", $instrumentName, $instrumentCode, $typeId, $deptId, $expiry, $notes, $loggedInUserId);
            if ($stmtInsert->execute()) {
                $successCount++;
            } else {
                $errorCount++; $errorDetails['db_error'][] = "Baris {$rowNum}";
            }
        }
        fclose($handle);
        $stmtInsert->close();
    }

    if ($errorCount > 0) {
        throw new Exception("Proses impor dihentikan karena ada kesalahan.");
    }

    $conn->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Impor Selesai! Berhasil menambahkan {$successCount} data instrumen baru."];

} catch (Exception $e) {
    $conn->rollback();
    
    // Membangun pesan error yang ringkas dan informatif
    $message = '<div style="text-align: left; max-width: 600px;">';

    if ($successCount > 0) {
        $message .= "<h4 style='font-size: 1.1rem; font-weight: 600; color: #9A3412;'>Impor Gagal Sebagian</h4>";
        $message .= "<p style='font-size: 0.9rem; margin-top: 4px;'>{$successCount} baris berhasil divalidasi, tetapi ditemukan {$errorCount} kesalahan. <strong>Tidak ada data yang disimpan.</strong></p>";
    } else {
        $message .= "<h4 style='font-size: 1.1rem; font-weight: 600; color: #991B1B;'>Impor Gagal</h4>";
        $message .= "<p style='font-size: 0.9rem; margin-top: 4px;'>Ditemukan {$errorCount} kesalahan dalam file CSV Anda. Tidak ada data yang diimpor.</p>";
    }

    $message .= "<div style='margin-top: 1rem; padding-top: 0.5rem; border-top: 1px solid #FECACA;'>";
    $message .= "<p style='font-size: 0.8rem; font-weight: 600; color: #4B5563; margin-bottom: 0.5rem;'>Ringkasan Kesalahan:</p>";
    $message .= "<ul style='list-style-type: disc; list-style-position: inside; font-size: 0.8rem; color: #57534E; space-y: 2px;'>";

    if (!empty($errorDetails['missing_data'])) { $message .= "<li><strong>Data Tidak Lengkap:</strong> " . count($errorDetails['missing_data']) . " baris.</li>"; }
    if (!empty($errorDetails['duplicate_name_db'])) { $message .= "<li><strong>Nama Duplikat:</strong> " . count($errorDetails['duplicate_name_db']) . " nama instrumen sudah ada di database.</li>"; }
    if (!empty($errorDetails['duplicate_code_db'])) { $message .= "<li><strong>Kode Duplikat:</strong> " . count($errorDetails['duplicate_code_db']) . " kode instrumen sudah ada.</li>"; }
    if (!empty($errorDetails['invalid_type'])) { $message .= "<li><strong>Tipe Tidak Valid:</strong> " . count($errorDetails['invalid_type']) . " nama tipe tidak ditemukan di Master Data.</li>"; }
    if (!empty($errorDetails['invalid_department'])) { $message .= "<li><strong>Departemen Tidak Valid:</strong> " . count($errorDetails['invalid_department']) . " nama departemen tidak ditemukan.</li>"; }
    if (!empty($errorDetails['db_error'])) { $message .= "<li><strong>Kesalahan Database:</strong> " . count($errorDetails['db_error']) . " baris gagal disimpan.</li>"; }
    
    $message .= "</ul></div>";
    $message .= "<p style='font-size: 0.8rem; margin-top: 1rem; color: #6B7280;'>Silakan perbaiki file CSV Anda dan coba lagi.</p>";
    $message .= '</div>';
    
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => $message];

} finally {
    $conn->close();
}

header("Location: ../manage_instruments.php");
exit();
?>