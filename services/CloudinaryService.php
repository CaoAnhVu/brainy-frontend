<?php
require_once __DIR__ . '/BaseService.php';

class CloudinaryService extends BaseService {
    private $cloudinaryConfig;

    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "cloudinary_files";
        
        // Cấu hình Cloudinary (bạn cần thay đổi thông tin này)
        $this->cloudinaryConfig = [
            'cloud_name' => 'your_cloud_name',
            'api_key' => 'your_api_key',
            'api_secret' => 'your_api_secret'
        ];
    }

    public function uploadFile($file, $ownerId, $ownerType, $fileType) {
        // Tạo tên file duy nhất
        $fileName = time() . '_' . $file['name'];
        $tempFilePath = $file['tmp_name'];
        
        // Upload lên Cloudinary (giả lập)
        $uploadResult = $this->uploadToCloudinary($tempFilePath, $fileName);
        
        if ($uploadResult) {
            // Lưu thông tin file vào database
            $fileData = [
                'owner_id' => $ownerId,
                'owner_type' => $ownerType,
                'file_type' => $fileType,
                'file_url' => $uploadResult['secure_url'],
                'public_id' => $uploadResult['public_id'],
                'format' => $uploadResult['format'] ?? null,
                'metadata' => json_encode($uploadResult['metadata'] ?? null),
                'status' => 'active'
            ];
            
            return $this->create($fileData);
        }
        
        return false;
    }
    
    public function getFilesByOwner($ownerId, $ownerType) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE owner_id = :owner_id AND owner_type = :owner_type AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":owner_id", $ownerId);
        $stmt->bindParam(":owner_type", $ownerType);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function softDelete($id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'deleted' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
    
    // Phương thức giả lập upload lên Cloudinary (bạn sẽ thay bằng API thật)
    private function uploadToCloudinary($filePath, $fileName) {
        // Trong thực tế, bạn sẽ sử dụng SDK của Cloudinary để upload
        // Đây chỉ là phương thức giả lập
        
        $publicId = 'brainy_' . str_replace([' ', '.'], ['_', '_'], $fileName);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        return [
            'public_id' => $publicId,
            'secure_url' => 'https://res.cloudinary.com/demo/' . $fileExtension . '/upload/' . $publicId,
            'format' => $fileExtension,
            'metadata' => [
                'original_filename' => $fileName,
                'size' => filesize($filePath)
            ]
        ];
    }
} 