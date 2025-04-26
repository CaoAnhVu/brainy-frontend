<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/UserService.php';

class RoleMiddleware {
    private $userService;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->userService = new UserService($db);
    }
    
    public function checkRole($requiredRole) {
        // Lấy user_id từ AuthMiddleware
        global $current_user_id;
        
        if (!$current_user_id) {
            http_response_code(401);
            echo json_encode(["message" => "Vui lòng đăng nhập"]);
            exit();
        }
        
        // Lấy thông tin người dùng
        $user = $this->userService->read($current_user_id);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Người dùng không tồn tại"]);
            exit();
        }
        
        // Kiểm tra role
        if ($user['role'] !== $requiredRole && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Bạn không có quyền thực hiện hành động này"]);
            exit();
        }
        
        return true;
    }
    
    public function isAdmin() {
        return $this->checkRole('admin');
    }
    
    public function isModerator() {
        return $this->checkRole('moderator');
    }
}