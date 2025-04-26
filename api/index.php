<?php
// API Entry Point
require_once __DIR__ . '/../config/cors.php';

// Lấy request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api';
$path = parse_url($requestUri, PHP_URL_PATH);

// Loại bỏ basePath từ đường dẫn
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Loại bỏ dấu / đầu tiên nếu có
$path = ltrim($path, '/');

// Route sang API endpoint phù hợp
switch ($path) {
    case '':
    case 'index.php':
        echo json_encode([
            'status' => 'success',
            'message' => 'Brainy API is running',
            'version' => '1.0.0'
        ]);
        break;
        
    case 'auth':
    case 'auth.php':
        require_once __DIR__ . '/auth.php';
        break;
        
    case (preg_match('/^auth\/(.+)$/', $path, $matches) ? true : false):
        $authEndpoint = $matches[1];
        if (file_exists(__DIR__ . "/auth/{$authEndpoint}.php")) {
            require_once __DIR__ . "/auth/{$authEndpoint}.php";
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'users':
    case 'users.php':
        require_once __DIR__ . '/users.php';
        break;
        
    case 'user-devices':
    case 'user-devices.php':
        require_once __DIR__ . '/user-devices.php';
        break;
        
    case 'sessions':
    case 'sessions.php':
        require_once __DIR__ . '/sessions.php';
        break;
        
    default:
        // Kiểm tra các API endpoint khác
        $endpoint = explode('/', $path)[0];
        if (file_exists(__DIR__ . "/{$endpoint}.php")) {
            require_once __DIR__ . "/{$endpoint}.php";
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
}