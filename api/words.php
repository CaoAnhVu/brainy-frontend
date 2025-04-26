<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../controllers/WordController.php';

$wordController = new WordController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['lesson_id'])) {
            $wordController->getByLesson();
        } elseif (isset($_GET['keyword'])) {
            $wordController->search();
        } else {
            $wordController->read();
        }
        break;

    case 'POST':
        $wordController->create();
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Phương thức không được phép"]);
        break;
} 