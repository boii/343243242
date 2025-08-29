<?php
// /api/index.php

declare(strict_types=1);

// --- PENANGANAN CORS DIMULAI DI SINI ---

// Tentukan domain frontend Anda yang diizinkan. 
// Untuk pengembangan, '*' bisa digunakan, tapi untuk produksi, ganti dengan domain spesifik.
$allowed_origin = '*'; 
// Contoh untuk produksi: $allowed_origin = 'https://aplikasi-frontend-anda.com';

header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization");

// Browser akan mengirim request pre-flight dengan method OPTIONS
// untuk request yang kompleks (seperti POST dengan body JSON).
// Kita harus menanggapinya dengan status 200 OK agar request sebenarnya bisa dilanjutkan.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- PENANGANAN CORS SELESAI ---


header('Content-Type: application/json');
require_once '../config.php';
require_once 'utils.php';

$conn = connectToDatabase();
if (!$conn) {
    http_response_code(503); // Service Unavailable
    echo json_encode([
        'status' => 'error',
        'message' => 'Tidak dapat terhubung ke layanan database.'
    ]);
    exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$apiKeyDetails = getApiKeyDetails($conn, $apiKey);

if (!$apiKeyDetails) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'status' => 'fail',
        'data' => ['authorization' => 'Akses ditolak. Kunci API tidak valid atau tidak diberikan.']
    ]);
    $conn->close();
    exit;
}

$requestPath = trim($_GET['request'] ?? '', '/');
$pathParts = explode('/', $requestPath);
$apiVersion = array_shift($pathParts) ?? '';
$resource = array_shift($pathParts) ?? '';
$param1 = array_shift($pathParts) ?? '';
$param2 = array_shift($pathParts) ?? '';

if ($apiVersion === 'v1') {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($resource) {
        case 'labels':
            require_once 'v1/labels.php';
            if ($method === 'GET' && !empty($param1) && $param2 === 'print') {
                handleGetLabelPrintHtml($conn, $param1);
            } elseif ($method === 'GET' && !empty($param1)) {
                handleGetLabelDetails($conn, $param1);
            } elseif ($method === 'POST' && !empty($param1) && $param2 === 'mark-used') {
                if ($apiKeyDetails['permissions'] !== 'read_write') {
                    http_response_code(403);
                    echo json_encode(['status' => 'fail', 'data' => ['authorization' => 'Kunci API ini tidak memiliki izin untuk menulis data.']]);
                    exit;
                }
                handleMarkLabelUsed($conn, $param1);
            }
            break;

        case 'loads':
            require_once 'v1/loads.php';
            require_once 'v1/process.php'; // Muat file proses yang baru
            if ($method === 'GET') {
                if (!empty($param1) && is_numeric($param1)) {
                    handleGetLoadDetails($conn, (int)$param1);
                } else {
                    handleGetLoadList($conn);
                }
            } elseif ($method === 'POST') {
                if ($apiKeyDetails['permissions'] !== 'read_write') {
                    http_response_code(403);
                    echo json_encode(['status' => 'fail', 'data' => ['authorization' => 'Kunci API ini tidak memiliki izin untuk menulis data.']]);
                    exit;
                }
                if (!empty($param1) && is_numeric($param1) && $param2 === 'items') {
                    handleAddItemToLoad($conn, (int)$param1);
                } elseif (!empty($param1) && is_numeric($param1) && $param2 === 'process') {
                    handleProcessLoad($conn, (int)$param1);
                } elseif (!empty($param1) && is_numeric($param1) && $param2 === 'generate-labels') {
                    handleGenerateLabels($conn, (int)$param1);
                } else {
                    handleCreateLoad($conn);
                }
            }
            break;
            
        // ... case lainnya (instruments, sets, dll) tetap sama ...

        case 'instruments':
            if ($method === 'GET') {
                require_once 'v1/instruments.php';
                if (!empty($param1) && is_numeric($param1)) {
                    handleGetInstrumentDetails($conn, (int)$param1);
                } else {
                    handleGetInstrumentList($conn);
                }
            }
            break;

        case 'sets':
            if ($method === 'GET') {
                require_once 'v1/sets.php';
                if (!empty($param1) && is_numeric($param1)) {
                    handleGetSetDetails($conn, (int)$param1);
                } else {
                    handleGetSetList($conn);
                }
            }
            break;

        case 'cycles':
            if ($method === 'GET') {
                require_once 'v1/cycles.php';
                if (!empty($param1) && is_numeric($param1)) {
                    handleGetCycleDetails($conn, (int)$param1);
                } else {
                    handleGetCycleList($conn);
                }
            }
            break;

        case 'reports':
            if ($method === 'GET') {
                require_once 'v1/reports.php';
                handleGetReports($conn);
            }
            break;

        case 'master':
            if ($method === 'GET' && !empty($param1)) {
                require_once 'v1/master_data.php';
                handleGetMasterData($conn, $param1);
            }
            break;

        case 'users':
            if ($method === 'GET') {
                require_once 'v1/users.php';
                if (!empty($param1) && is_numeric($param1)) {
                    handleGetUserDetails($conn, (int)$param1);
                } else {
                    handleGetUserList($conn);
                }
            }
            break;

        default:
            http_response_code(404);
            echo json_encode([
                'status' => 'fail',
                'data' => ['resource' => 'Resource tidak ditemukan.']
            ]);
            break;
    }

    $conn->close();
    exit;
}

// Fallback
http_response_code(404);
echo json_encode([
    'status' => 'fail',
    'data' => [
        'endpoint' => 'Endpoint tidak ditemukan atau versi API tidak didukung.',
        'requested_path' => $requestPath
    ]
]);

$conn->close();
exit;