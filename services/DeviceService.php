<?php
require_once __DIR__ . '/BaseService.php';

class DeviceService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "user_devices";
    }
    
    /**
     * Đăng ký thiết bị mới hoặc cập nhật thông tin thiết bị hiện có
     * 
     * @param string $userId ID của người dùng
     * @param array $deviceData Thông tin thiết bị
     * @return string|false ID của thiết bị nếu thành công, false nếu thất bại
     */
    public function registerDevice($userId, $deviceData) {
        // Kiểm tra xem thiết bị đã tồn tại chưa
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND device_id = :device_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":device_id", $deviceData['deviceId']);
        $stmt->execute();
        
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($device) {
            // Cập nhật thiết bị hiện có
            $query = "UPDATE " . $this->table_name . " 
                      SET device_name = :device_name, 
                          device_type = :device_type, 
                          last_login_at = NOW(),
                          is_active = 1
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":device_name", $deviceData['deviceName']);
            $stmt->bindParam(":device_type", $deviceData['deviceType']);
            $stmt->bindParam(":id", $device['id']);
            
            return $stmt->execute() ? $device['id'] : false;
        } else {
            // Tạo mới thiết bị
            $deviceId = $this->generateUUID();
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (id, user_id, device_id, device_name, device_type, last_login_at, is_active) 
                      VALUES (:id, :user_id, :device_id, :device_name, :device_type, NOW(), 1)";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $deviceId);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":device_id", $deviceData['deviceId']);
            $stmt->bindParam(":device_name", $deviceData['deviceName']);
            $stmt->bindParam(":device_type", $deviceData['deviceType']);
            
            return $stmt->execute() ? $deviceId : false;
        }
    }
    
    /**
     * Lấy danh sách thiết bị của người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách thiết bị
     */
    public function getUserDevices($userId) {
        $query = "SELECT id, device_id, device_name, device_type, last_login_at, is_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY last_login_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vô hiệu hóa một thiết bị
     * 
     * @param string $id ID của thiết bị
     * @return boolean Kết quả thực hiện
     */
    public function deactivateDevice($id) {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Vô hiệu hóa tất cả thiết bị của người dùng, trừ một thiết bị cụ thể
     * 
     * @param string $userId ID của người dùng
     * @param string $exceptDeviceId ID của thiết bị không muốn vô hiệu hóa
     * @return int Số thiết bị đã vô hiệu hóa
     */
    public function deactivateAllDevicesExcept($userId, $exceptDeviceId) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = 0 
                  WHERE user_id = :user_id AND id != :except_device_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":except_device_id", $exceptDeviceId);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Xóa các thiết bị không hoạt động quá lâu
     * 
     * @param int $days Số ngày không hoạt động trước khi xóa
     * @return int Số thiết bị đã xóa
     */
    public function cleanInactiveDevices($days = 180) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE is_active = 0 AND last_login_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
}