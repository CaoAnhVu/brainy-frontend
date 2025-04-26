<?php
require_once __DIR__ . '/BaseService.php';

class TwoFactorAuthService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "two_factor_auth";
    }
    
    /**
     * Kiểm tra xem người dùng đã bật 2FA chưa
     * 
     * @param string $userId ID của người dùng
     * @return boolean True nếu đã bật, false nếu chưa
     */
    public function isEnabled($userId) {
        $query = "SELECT is_enabled FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['is_enabled'] == 1;
    }
    
    /**
     * Bật xác thực 2 lớp cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array|false Thông tin secret key và backup codes nếu thành công, false nếu thất bại
     */
    public function enableTwoFactor($userId) {
        // Tạo secret key
        $secretKey = $this->generateSecretKey();
        
        // Tạo backup codes
        $backupCodes = $this->generateBackupCodes();
        
        // Kiểm tra xem user đã có 2FA chưa
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update
            $query = "UPDATE " . $this->table_name . " 
                      SET secret_key = :secret_key, backup_codes = :backup_codes, is_enabled = 1 
                      WHERE user_id = :user_id";
        } else {
            // Insert
            $query = "INSERT INTO " . $this->table_name . " 
                      (id, user_id, secret_key, backup_codes, is_enabled) 
                      VALUES (:id, :user_id, :secret_key, :backup_codes, 1)";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!$existing) {
            $id = $this->generateUUID();
            $stmt->bindParam(":id", $id);
        }
        
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":secret_key", $secretKey);
        $backupCodesJson = json_encode($backupCodes);
        $stmt->bindParam(":backup_codes", $backupCodesJson);
        
        return $stmt->execute() ? ['secret' => $secretKey, 'backup_codes' => $backupCodes] : false;
    }
    
    /**
     * Vô hiệu hóa xác thực 2 lớp
     * 
     * @param string $userId ID của người dùng
     * @return boolean Kết quả thực hiện
     */
    public function disableTwoFactor($userId) {
        $query = "UPDATE " . $this->table_name . " SET is_enabled = 0 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Xác thực mã OTP hoặc backup code
     * 
     * @param string $userId ID của người dùng
     * @param string $code Mã OTP hoặc backup code
     * @return boolean True nếu mã hợp lệ, false nếu không
     */
    public function verifyTwoFactor($userId, $code) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND is_enabled = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $twoFactor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$twoFactor) {
            return true; // 2FA không bật, mọi code đều hợp lệ
        }
        
        // Kiểm tra backup codes
        $backupCodes = json_decode($twoFactor['backup_codes'], true);
        if ($backupCodes && in_array($code, $backupCodes)) {
            // Xóa backup code đã sử dụng
            $backupCodes = array_diff($backupCodes, [$code]);
            $this->updateBackupCodes($userId, $backupCodes);
            return true;
        }
        
        // Kiểm tra TOTP code
        return $this->verifyTOTP($twoFactor['secret_key'], $code);
    }
    
    /**
     * Tạo lại backup codes
     * 
     * @param string $userId ID của người dùng
     * @return array|false Danh sách backup codes mới nếu thành công, false nếu thất bại
     */
    public function regenerateBackupCodes($userId) {
        $backupCodes = $this->generateBackupCodes();
        
        $query = "UPDATE " . $this->table_name . " SET backup_codes = :backup_codes WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $backupCodesJson = json_encode($backupCodes);
        $stmt->bindParam(":backup_codes", $backupCodesJson);
        $stmt->bindParam(":user_id", $userId);
        
        return $stmt->execute() ? $backupCodes : false;
    }
    
    /**
     * Tạo secret key
     * 
     * @return string Secret key
     */
    private function generateSecretKey() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 character set
        $secret = '';
        $length = 16; // 16 characters for 80-bit secret
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        
        return $secret;
    }
    
    /**
     * Tạo backup codes
     * 
     * @param int $count Số lượng backup codes
     * @return array Danh sách backup codes
     */
    private function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = substr(bin2hex(random_bytes(4)), 0, 8);
        }
        return $codes;
    }
    
    /**
     * Cập nhật backup codes
     * 
     * @param string $userId ID của người dùng
     * @param array $backupCodes Danh sách backup codes mới
     * @return boolean Kết quả cập nhật
     */
    private function updateBackupCodes($userId, $backupCodes) {
        $query = "UPDATE " . $this->table_name . " SET backup_codes = :backup_codes WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $backupCodesJson = json_encode($backupCodes);
        $stmt->bindParam(":backup_codes", $backupCodesJson);
        $stmt->bindParam(":user_id", $userId);
        return $stmt->execute();
    }
    
    /**
     * Xác thực mã TOTP
     * 
     * @param string $secretKey Secret key
     * @param string $code Mã OTP cần xác thực
     * @return boolean True nếu mã hợp lệ, false nếu không
     */
    private function verifyTOTP($secretKey, $code) {
        // Trong môi trường thực tế, bạn nên sử dụng thư viện TOTP như:
        // https://github.com/RobThree/TwoFactorAuth
        
        // Đây là triển khai đơn giản để minh họa
        // Trong thực tế, bạn cần kiểm tra mã TOTP dựa trên thuật toán RFC 6238
        
        // Kiểm tra đơn giản: mã phải là 6 chữ số
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        
        // Tạo mã TOTP cho thời điểm hiện tại
        $expectedCode = $this->generateTOTP($secretKey);
        
        // Kiểm tra mã
        return $code === $expectedCode;
    }
    
    /**
     * Tạo mã TOTP dựa trên secret key và thời gian hiện tại
     * 
     * @param string $secretKey Secret key
     * @return string Mã TOTP 6 chữ số
     */
    private function generateTOTP($secretKey) {
        // Trong thực tế, bạn nên sử dụng thư viện TOTP
        // Đây chỉ là triển khai mẫu
        
        // Trả về một mã 6 chữ số ngẫu nhiên (chỉ để minh họa)
        // Trong ứng dụng thực tế, mã này phải được tính từ secret key và timestamp
        return sprintf('%06d', random_int(0, 999999));
    }
}