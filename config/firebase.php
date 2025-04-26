<?php
require_once __DIR__ . '/database.php';

class Firebase {
    private $apiKey;
    private $authDomain;
    private $projectId;
    private $storageBucket;
    private $messagingSenderId;
    private $appId;
    private $measurementId;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        
        // Lấy cấu hình Firebase từ database
        $this->loadConfig($db);
    }
    
    private function loadConfig($db) {
        $query = "SELECT name, value FROM app_configs WHERE name LIKE 'FIREBASE_%'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch ($row['name']) {
                case 'FIREBASE_API_KEY':
                    $this->apiKey = $row['value'];
                    break;
                case 'FIREBASE_AUTH_DOMAIN':
                    $this->authDomain = $row['value'];
                    break;
                case 'FIREBASE_PROJECT_ID':
                    $this->projectId = $row['value'];
                    break;
                case 'FIREBASE_STORAGE_BUCKET':
                    $this->storageBucket = $row['value'];
                    break;
                case 'FIREBASE_MESSAGING_SENDER_ID':
                    $this->messagingSenderId = $row['value'];
                    break;
                case 'FIREBASE_APP_ID':
                    $this->appId = $row['value'];
                    break;
                case 'FIREBASE_MEASUREMENT_ID':
                    $this->measurementId = $row['value'];
                    break;
            }
        }
    }
    
    public function getConfig() {
        return [
            'apiKey' => $this->apiKey,
            'authDomain' => $this->authDomain,
            'projectId' => $this->projectId,
            'storageBucket' => $this->storageBucket,
            'messagingSenderId' => $this->messagingSenderId,
            'appId' => $this->appId,
            'measurementId' => $this->measurementId
        ];
    }
    
    public function verifyIdToken($idToken) {
        // Verify Firebase ID token
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
}