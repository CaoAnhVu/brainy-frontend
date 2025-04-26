<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../config/database.php';

class FirebaseService extends BaseService {
    private $apiKey;
    private $authDomain;
    private $projectId;
    
    public function __construct($db) {
        parent::__construct($db);
        
        // Lấy cấu hình từ bảng app_configs
        $this->loadFirebaseConfig();
    }
    
    private function loadFirebaseConfig() {
        $query = "SELECT name, value FROM app_configs WHERE name LIKE 'FIREBASE%'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($configs as $config) {
            if ($config['name'] == 'FIREBASE_API_KEY') {
                $this->apiKey = $config['value'];
            } elseif ($config['name'] == 'FIREBASE_AUTH_DOMAIN') {
                $this->authDomain = $config['value'];
            } elseif ($config['name'] == 'FIREBASE_PROJECT_ID') {
                $this->projectId = $config['value'];
            }
        }
    }
    
    public function verifyIdToken($idToken) {
        // Sử dụng Google API Client để xác thực token
        // Bạn cần cài đặt thư viện google/auth
        
        // Mock implementation
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=" . $this->apiKey);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function storeAuthProvider($userId, $providerData) {
        // Lưu thông tin social provider vào bảng social_auth_providers
        $providerId = $this->generateUUID();
        
        $query = "INSERT INTO social_auth_providers 
                  (id, user_id, provider, provider_id, provider_email, access_token, expires_at) 
                  VALUES (:id, :user_id, :provider, :provider_id, :provider_email, :access_token, :expires_at)
                  ON DUPLICATE KEY UPDATE 
                  access_token = :access_token, 
                  expires_at = :expires_at";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $providerId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":provider", $providerData['provider']);
        $stmt->bindParam(":provider_id", $providerData['providerId']);
        $stmt->bindParam(":provider_email", $providerData['email']);
        $stmt->bindParam(":access_token", $providerData['accessToken']);
        $stmt->bindParam(":expires_at", $providerData['expiresAt']);
        
        return $stmt->execute();
    }
}
