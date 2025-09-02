<?php
/**
 * Download Instrument Import Template
 *
 * Generates and serves a CSV file with a header row and sample data
 * for importing instruments.
 */
declare(strict_types=1);

require_once '../config.php';

// Authorization check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    die("Akses ditolak.");
}

$filename = "template_impor_instrumen_dengan_contoh.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM untuk kompatibilitas UTF-8 dengan Excel
fputs($output, "\xEF\xBB\xBF");

// Header yang dibutuhkan
$headers = [
    'instrument_name',      // Wajib
    'instrument_code',      // Opsional (kosongkan untuk dibuat otomatis)
    'type_name',            // Wajib, harus cocok dengan Master Data
    'department_name',      // Wajib, harus cocok dengan Master Data
    'expiry_in_days',       // Opsional
    'notes'                 // Opsional
];
fputcsv($output, $headers);

// Menambahkan data contoh
$sampleData = [
    [
        'instrument_name' => 'Gunting Bedah Metzenbaum',
        'instrument_code' => 'GB-METZ-01',
        'type_name' => 'Instrument Bedah',
        'department_name' => 'IBS',
        'expiry_in_days' => '365',
        'notes' => 'Ujung tajam, panjang 18 cm'
    ],
    [
        'instrument_name' => 'Klem Arteri Pean',
        'instrument_code' => '', // Dikosongkan agar dibuat otomatis oleh sistem
        'type_name' => 'Instrument Bedah',
        'department_name' => 'IBS',
        'expiry_in_days' => '365',
        'notes' => ''
    ],
    [
        'instrument_name' => 'Pinset Anatomis',
        'instrument_code' => 'PNST-ANT-05',
        'type_name' => 'Instrument Bedah',
        'department_name' => 'CSSD',
        'expiry_in_days' => '', // Menggunakan pengaturan global
        'notes' => 'Ukuran sedang'
    ]
];

foreach ($sampleData as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>