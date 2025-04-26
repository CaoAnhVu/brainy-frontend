<?php
require_once __DIR__ . '/BaseService.php';

class SessionService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "user_sessions";
    }
    
    /**
     * Tạo phiên đăng nhập mới cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @param string $token JWT token được cấp
     * @param string $ipAddress Địa chỉ IP của người dùng
     * @param string $userAgent User agent của trình duyệt/thiết bị
     * @return string|false ID của phiên nếu thành công, false nếu thất bại
     */
    public function createSession($userId, $token, $ipAddress, $userAgent) {
        $sessionId = $this->generateUUID();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (id, user_id, token, ip_address, user_agent, expires_at) 
                  VALUES (:id, :user_id, :token, :ip_address, :user_agent, :expires_at)";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $sessionId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":ip_address", $ipAddress);
        $stmt->bindParam(":user_agent", $userAgent);
        $stmt->bindParam(":expires_at", $expiresAt);
        
        if ($stmt->execute()) {
            return $sessionId;
        }
        return false;
    }
    
    /**
     * Kiểm tra xem một phiên đăng nhập có hợp lệ không
     * 
     * @param string $token JWT token cần kiểm tra
     * @return array|false Thông tin phiên nếu hợp lệ, false nếu không hợp lệ
     */
    public function validateSession($token) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE token = :token AND expires_at > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cập nhật thời gian hoạt động cuối cùng của phiên
     * 
     * @param string $token JWT token của phiên
     * @return boolean Kết quả cập nhật
     */
    public function updateSessionActivity($token) {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_activity = CURRENT_TIMESTAMP 
                  WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        
        return $stmt->execute();
    }
    
    /**
     * Xóa một phiên đăng nhập cụ thể
     * 
     * @param string $token JWT token của phiên cần xóa
     * @return boolean Kết quả xóa
     */
    public function deleteSession($token) {
        $query = "DELETE FROM " . $this->table_name . " WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        
        return $stmt->execute();
    }
    
    /**
     * Xóa tất cả phiên đăng nhập của một người dùng
     * 
     * @param string $userId ID của người dùng
     * @param string $exceptToken [Tùy chọn] Token không muốn xóa (phiên hiện tại)
     * @return boolean Kết quả xóa
     */
    public function deleteUserSessions($userId, $exceptToken = null) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        
        if ($exceptToken) {
            $query .= " AND token != :token";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        
        if ($exceptToken) {
            $stmt->bindParam(":token", $exceptToken);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Lấy danh sách phiên đăng nhập của một người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách phiên đăng nhập
     */
    public function getUserSessions($userId) {
        $query = "SELECT id, ip_address, user_agent, last_activity, created_at 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY last_activity DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Xóa các phiên đăng nhập hết hạn
     * 
     * @return int Số phiên đã được xóa
     */
    public function cleanExpiredSessions() {
        $query = "DELETE FROM " . $this->table_name . " WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Gia hạn thời gian hết hạn cho một phiên
     * 
     * @param string $token JWT token của phiên
     * @param int $days Số ngày gia hạn
     * @return boolean Kết quả gia hạn
     */
    public function extendSession($token, $days = 30) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        $query = "UPDATE " . $this->table_name . " 
                  SET expires_at = :expires_at 
                  WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":expires_at", $expiresAt);
        $stmt->bindParam(":token", $token);
        
        return $stmt->execute();
    }
}