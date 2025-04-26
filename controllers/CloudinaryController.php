<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/CloudinaryService.php';

class CloudinaryController {
    private $cloudinaryService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->cloudinaryService = new CloudinaryService($db);
    }

    public function upload() {
        // Kiểm tra xem có file được gửi lên không
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["message" => "Không có file hoặc file không hợp lệ"]);
            return;
        }
        
        // Lấy thông tin từ request
        $ownerId = $_POST['owner_id'] ?? null;
        $ownerType = $_POST['owner_type'] ?? null;
        $fileType = $_POST['file_type'] ?? 'image';
        
        if (!$ownerId || !$ownerType) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin owner_id hoặc owner_type"]);
            return;
        }
        
        // Kiểm tra định dạng file
        $allowedFileTypes = ['image' => ['jpg', 'jpeg', 'png', 'gif'], 
                            'audio' => ['mp3', 'wav', 'ogg'],
                            'video' => ['mp4', 'webm', 'ogv'],
                            'document' => ['pdf', 'doc', 'docx']];
        
        $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedFileTypes[$fileType])) {
            http_response_code(400);
            echo json_encode(["message" => "Định dạng file không được hỗ trợ"]);
            return;
        }
        
        // Upload file
        $fileId = $this->cloudinaryService->uploadFile($_FILES['file'], $ownerId, $ownerType, $fileType);
        
        if ($fileId) {
            $fileInfo = $this->cloudinaryService->read($fileId);
            http_response_code(201);
            echo json_encode([
                "message" => "Upload file thành công",
                "file" => $fileInfo
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Không thể upload file"]);
        }
    }

    public function getFiles() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if ($id) {
            $file = $this->cloudinaryService->read($id);
            if ($file) {
                http_response_code(200);
                echo json_encode($file);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Không tìm thấy file"]);
            }
            return;
        }
        
        $ownerId = isset($_GET['owner_id']) ? $_GET['owner_id'] : null;
        $ownerType = isset($_GET['owner_type']) ? $_GET['owner_type'] : null;
        
        if ($ownerId && $ownerType) {
            $files = $this->cloudinaryService->getFilesByOwner($ownerId, $ownerType);
            http_response_code(200);
            echo json_encode($files);
        } else {
            $files = $this->cloudinaryService->read();
            http_response_code(200);
            echo json_encode($files);
        }
    }

    public function delete() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID file"]);
            return;
        }
        
        $result = $this->cloudinaryService->softDelete($data->id);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Xóa file thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể xóa file"]);
        }
    }
} 