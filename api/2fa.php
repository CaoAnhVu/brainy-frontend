<?php
require_once __DIR__ . '/../controllers/TwoFactorAuthController.php';
require_once __DIR__ . '/../utils/JwtUtil.php';
require_once __DIR__ . '/../utils/ResponseUtil.php';
require_once __DIR__ . '/../utils/ValidationUtil.php';

header('Content-Type: application/json');

// Khởi tạo các utility classes
$jwt = new JwtUtil();
$controller = new TwoFactorAuthController();

// Xác thực JWT token
$token = null;
$headers = apache_request_headers();
if(isset($headers['Authorization'])) {
    $matches = [];
    preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
    if(isset($matches[1])) {
        $token = $matches[1];
    }
}

if (!$token || !$jwt->validateToken($token)) {
    echo ResponseUtil::unauthorized('Invalid or missing token');
    exit;
}

// Lấy thông tin user từ token
$user = $jwt->getPayload($token);
if (!$user || !isset($user['id']) || !ValidationUtil::validateUserId($user['id'])) {
    echo ResponseUtil::unauthorized('Invalid token payload');
    exit;
}

// Lấy method và action từ request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? ValidationUtil::sanitizeInput($_GET['action']) : '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'status':
                    $status = $controller->get2FAStatus($user['id']);
                    echo ResponseUtil::success($status);
                    break;

                case 'setup':
                    $setupData = $controller->getSetupData($user['id']);
                    echo ResponseUtil::success($setupData);
                    break;
                    
                case 'backup-codes':
                    $backupCodes = $controller->getBackupCodes($user['id']);
                    echo ResponseUtil::success($backupCodes);
                    break;
                    
                default:
                    echo ResponseUtil::notFound('Route not found');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $data = ValidationUtil::sanitizeInput($data);
            
            switch ($action) {
                case 'enable':
                    if (!isset($data['code']) || !ValidationUtil::validateTwoFactorCode($data['code'])) {
                        echo ResponseUtil::validation(['code' => 'Invalid verification code']);
                        break;
                    }
                    $result = $controller->enable2FA($user['id'], $data['code']);
                    echo ResponseUtil::success($result, '2FA has been enabled successfully');
                    break;
                    
                case 'verify':
                    if (!isset($data['code']) || !ValidationUtil::validateTwoFactorCode($data['code'])) {
                        echo ResponseUtil::validation(['code' => 'Invalid verification code']);
                        break;
                    }
                    $result = $controller->verify2FA($user['id'], $data['code']);
                    echo ResponseUtil::success($result, 'Verification successful');
                    break;
                    
                case 'disable':
                    if (!isset($data['code']) || !ValidationUtil::validateTwoFactorCode($data['code'])) {
                        echo ResponseUtil::validation(['code' => 'Invalid verification code']);
                        break;
                    }
                    $result = $controller->disable2FA($user['id'], $data['code']);
                    echo ResponseUtil::success($result, '2FA has been disabled successfully');
                    break;
                    
                case 'verify-backup':
                    if (!isset($data['code']) || !ValidationUtil::validateBackupCode($data['code'])) {
                        echo ResponseUtil::validation(['code' => 'Invalid backup code']);
                        break;
                    }
                    $result = $controller->verifyBackupCode($user['id'], $data['code']);
                    echo ResponseUtil::success($result, 'Backup code verification successful');
                    break;
                    
                default:
                    echo ResponseUtil::notFound('Route not found');
            }
            break;

        default:
            echo ResponseUtil::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    echo ResponseUtil::error($e->getMessage());
}