<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../controllers/AuthController.php';
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $auth = new AuthController();
    $data = json_decode(file_get_contents('php://input'), true);

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'));
                if (!isset($data->email) || !isset($data->password) || !isset($data->name)) {
                    http_response_code(400);
                    echo json_encode(["message" => "Thiếu thông tin đăng ký"]);
                    return;
                }
                echo $auth->register($data);
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Phương thức không được phép"]);
            }
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->login();
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Phương thức không được phép"]);
            }
            break;

        case 'refresh-token':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->refreshToken();
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Phương thức không được phép"]);
            }
            break;

        case 'forgot-password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->forgotPassword();
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Phương thức không được phép"]);
            }
            break;

        case 'reset-password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->resetPassword();
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Phương thức không được phép"]);
            }
            break;

        case 'google-auth':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->googleAuth();
            } else {
                http_response_code(405);
                echo json_encode(["message" => "Phương thức không được phép"]);
            }
            break;

        case 'verify-otp':
            echo $auth->verifyOTP($data);
            break;

        default:
            http_response_code(404);
            echo json_encode(["message" => "Endpoint không tồn tại"]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Lỗi server",
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
} 