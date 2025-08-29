<?php
// /api/index.php

declare(strict_types=1);

header('Content-Type: application/json');
require_once '../config.php';
require_once 'utils.php';

$conn = connectToDatabase();
if (!$conn) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Tidak dapat terhubung ke layanan.']);
    exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!validateApiKey($conn, $apiKey)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak. Kunci API tidak valid atau tidak diberikan.']);
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
            if ($method === 'GET' && !empty($param1)) {
                handleGetLabelDetails($conn, $param1);
            } elseif ($method === 'POST' && !empty($param1) && $param2 === 'mark-used') {
                handleMarkLabelUsed($conn, $param1);
            }
            break;

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

        case 'loads':
            require_once 'v1/loads.php';
            if ($method === 'GET') {
                if (!empty($param1) && is_numeric($param1)) {
                    handleGetLoadDetails($conn, (int)$param1);
                } else {
                    handleGetLoadList($conn);
                }
            } elseif ($method === 'POST') {
                if (!empty($param1) && is_numeric($param1) && $param2 === 'items') {
                    // Rute baru: POST /loads/{id}/items
                    handleAddItemToLoad($conn, (int)$param1);
                } else {
                    // Rute lama: POST /loads
                    handleCreateLoad($conn);
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
            echo json_encode(['success' => false, 'error' => 'Resource tidak ditemukan.']);
            break;
    }

    $conn->close();
    exit;
}

// Fallback
http_response_code(404);
echo json_encode([
    'success' => false,
    'error' => 'Endpoint tidak ditemukan atau versi API tidak didukung.',
    'requested_path' => $requestPath
]);

$conn->close();
exit;