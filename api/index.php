<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!defined('IN_LR')) {
    define('IN_LR', true);
}

if (!defined('STORAGE')) {
<<<<<<< HEAD
    $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
    define('STORAGE', $rootPath . '/storage/');
}

$moduleDir = dirname(__DIR__);
require_once($moduleDir . '/forward/data.php');
=======
    define('STORAGE', '../../../../storage/');
}
if (!defined('MODULESCACHE')) {
    define('MODULESCACHE', STORAGE . 'modules_cache/');
}

require_once(__DIR__ . '/../forward/data.php');
>>>>>>> 0c32d38afa72eb973481df02bfd15ac2784578a2

$SurfRecords = new SurfRecordsModule();

if (!$SurfRecords->isConnected()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection unavailable',
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$endpoint = $_GET['endpoint'] ?? 'stats';

function sanitizeInput($input) {
    if (is_string($input)) {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    return $input;
}

$endpoint = sanitizeInput($endpoint);
$allowed_endpoints = ['stats', 'maps', 'records', 'map_info'];
if (!in_array($endpoint, $allowed_endpoints)) {
    sendError('Invalid endpoint', 400);
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function sendError($message, $statusCode = 400) {
    sendResponse([
        'success' => false,
        'error' => $message,
        'timestamp' => time()
    ], $statusCode);
}

function validateMapName($map) {
    if (empty($map) || !is_string($map)) {
        return false;
    }
    
    if (strlen($map) > 64 || strlen($map) < 1) {
        return false;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $map)) {
        return false;
    }
    
    $dangerous_patterns = [
        '/union/i', '/select/i', '/insert/i', '/update/i', '/delete/i',
        '/drop/i', '/create/i', '/alter/i', '/exec/i', '/script/i',
        '/<script/i', '/javascript:/i', '/vbscript:/i', '/onload/i',
        '/onerror/i', '/onclick/i'
    ];
    
    foreach ($dangerous_patterns as $pattern) {
        if (preg_match($pattern, $map)) {
            return false;
        }
    }
    
    return true;
}

<<<<<<< HEAD
=======
function validateSteamID64($steamid) {
    return preg_match('/^7656119[0-9]{10}$/', $steamid);
}


>>>>>>> 0c32d38afa72eb973481df02bfd15ac2784578a2
try {
    switch ($endpoint) {
        
        case 'stats':
            $stats = $SurfRecords->getStatistics();
            sendResponse([
                'success' => true,
                'data' => $stats,
                'timestamp' => time()
            ]);
            break;
        
        case 'maps':
            $category = $_GET['category'] ?? null;

            if ($category) {
                $category = sanitizeInput($category);
                if (!in_array($category, ['surf', 'kz', 'bhop', 'other'])) {
                    sendError('Invalid category');
                }
            }
            
            $maps = $SurfRecords->getMaps($category);
            sendResponse([
                'success' => true,
                'data' => $maps,
                'timestamp' => time()
            ]);
            break;
        
        case 'records':
            $map = $_GET['map'] ?? null;
            
            if (!$map) {
                sendError('Map parameter is required');
            }
            
            $map = sanitizeInput($map);
            
            if (!validateMapName($map)) {
                sendError('Invalid map name format');
            }
            
            $records = $SurfRecords->getMapRecords($map);
            sendResponse([
                'success' => true,
                'data' => [
                    'map' => $map,
                    'records' => $records,
                    'count' => count($records)
                ],
                'timestamp' => time()
            ]);
            break;
        
        case 'map_info':
            $map = $_GET['map'] ?? null;
            
            if (!$map) {
                sendError('Map parameter is required');
            }
            
            $map = sanitizeInput($map);
            
            if (!validateMapName($map)) {
                sendError('Invalid map name format');
            }
            
            $records = $SurfRecords->getMapRecords($map);
            $top_record = !empty($records) ? $records[0] : null;
            
            sendResponse([
                'success' => true,
                'data' => [
                    'map_name' => $map,
                    'top_record' => $top_record,
                    'has_records' => !empty($records),
                    'total_records' => count($records)
                ],
                'timestamp' => time()
            ]);
            break;
        
        default:
            sendError('Unknown API endpoint: ' . $endpoint, 404);
    }
    
} catch (Exception $e) {
    error_log("Surf API Error: " . $e->getMessage());
    sendError('Internal server error', 500);
}