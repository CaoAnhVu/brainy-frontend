<?php
// Đọc .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
        }
    }
}

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../utils/ResponseUtil.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = [
            'to' => 'anhvuktnh@gmail.com',
            'subject' => 'Test Email from Brainy App',
            'message' => 'This is a test email from Brainy App!'
        ];
    }

    $mail = new Mail();
    $result = $mail->send($data['to'], $data['subject'], $data['message']);
    
    if ($result) {
        echo ResponseUtil::success([
            'to' => $data['to'],
            'subject' => $data['subject']
        ], 'Test email sent successfully!');
    } else {
        echo ResponseUtil::error('Failed to send test email');
    }
} catch (Exception $e) {
    echo ResponseUtil::error('Error: ' . $e->getMessage());
}