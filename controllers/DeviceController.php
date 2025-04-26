<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/DeviceService.php';

class DeviceController {
    private $deviceService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->deviceService = new DeviceService($db);
    }

    public function getUserDevices() {
        // Lấy user_id từ JWT token
        $userId = $this->getUserIdFromToken();
        
        $devices = $this->deviceService->getUserDevices($userId);
        
        http_response_code(200);
        echo json_encode(['devices' => $devices]);
    }

    public function registerDevice() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->device_id) || !isset($data->device_name) || !isset($data->device_type)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin thiết bị"]);
            return;
        }
        
        $userId = $this->getUserIdFromToken();
        
        $result = $this->deviceService->registerDevice($userId, [
            'deviceId' => $data->device_id,
            'deviceName' => $data->device_name,
            'deviceType' => $data->device_type
        ]);
        
        if ($result) {
            http_response_code(201);
            echo json_encode(["message" => "Đăng ký thiết bị thành công", "id" => $result]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Không thể đăng ký thiết bị"]);
        }
    }

    public function deactivateDevice() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->device_id)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID thiết bị"]);
            return;
        }
        
        $userId = $this->getUserIdFromToken();
        
        // Kiểm tra xem thiết bị có thuộc về user không
        $devices = $this->deviceService->getUserDevices($userId);
        $deviceFound = false;
        
        foreach ($devices as $device) {
            if ($device['id'] === $data->device_id) {
                $deviceFound = true;
                break;
            }
        }
        
        if (!$deviceFound) {
            http_response_code(403);
            echo json_encode(["message" => "Bạn không có quyền với thiết bị này"]);
            return;
        }
        
        $result = $this->deviceService->deactivateDevice($data->device_id);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Đã vô hiệu hóa thiết bị"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Không thể vô hiệu hóa thiết bị"]);
        }
    }

    private function getUserIdFromToken() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            $tokenParts = explode('.', $jwt);
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            $payloadData = json_decode($payload, true);
            
            return $payloadData['user_id'] ?? null;
        }
        
        return null;
    }
}