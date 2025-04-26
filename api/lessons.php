<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../controllers/LessonController.php';

$lessonController = new LessonController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $lessonController->read();
        break;

    case 'POST':
        $lessonController->create();
        break;

    case 'PUT':
        if (isset($_GET['action']) && $_GET['action'] === 'order') {
            $lessonController->updateOrder();
        } else {
            $lessonController->update();
        }
        break;

    case 'DELETE':
        $lessonController->delete();
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Phương thức không được phép"]);
        break;
} 