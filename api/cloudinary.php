<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,DELETE,OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../controllers/CloudinaryController.php';

$cloudinaryController = new CloudinaryController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $cloudinaryController->getFiles();
        break;

    case 'POST':
        $cloudinaryController->upload();
        break;

    case 'DELETE':
        $cloudinaryController->delete();
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Phương thức không được phép"]);
        break;
} 