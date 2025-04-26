<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AuthService.php';

class AuthMiddleware {
    private $authService;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->authService = new AuthService($db);
    }
    
    public function authenticate() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(["message" => "Vui lòng đăng nhập"]);
            exit();
        }
        
        $token = $matches[1];
        $userData = $this->authService->validateToken($token);
        
        if (!$userData) {
            http_response_code(401);
            echo json_encode(["message" => "Phiên đăng nhập hết hạn hoặc không hợp lệ"]);
            exit();
        }
        
        // Lưu thông tin người dùng vào biến toàn cầu để sử dụng sau này
        global $current_user_id;
        $current_user_id = $userData['user_id'];
        
        return true;
    }
    
    // Hàm lấy user_id từ token hiện tại
    public static function getCurrentUserId() {
        global $current_user_id;
        return $current_user_id ?? null;
    }
}