<?php
require_once 'controllers/BaseController.php';
require_once 'services/NotificationService.php';
require_once 'services/UserService.php';

class NotificationController extends BaseController {
    private $notificationService;
    private $userService;
    
    public function __construct() {
        parent::__construct();
        $this->notificationService = new NotificationService($this->conn);
        $this->userService = new UserService($this->conn);
    }
    
    /**
     * Lấy danh sách thông báo của người dùng
     */
    public function getUserNotifications() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy tham số từ request
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            // Lấy danh sách thông báo
            $notifications = $this->notificationService->getUserNotifications($userId, $limit, $offset);
            
            // Đếm số lượng thông báo chưa đọc
            $query = "SELECT COUNT(*) AS unread_count FROM notifications 
                     WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => (int)$unreadCount
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách thông báo: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['notificationId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'ID thông báo là bắt buộc'
                ]);
                return;
            }
            
            $notificationId = $data['notificationId'];
            
            // Đánh dấu đã đọc
            $result = $this->notificationService->markAsRead($notificationId, $userId);
            
            if (!$result) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không thể đánh dấu thông báo đã đọc'
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Đã đánh dấu thông báo đã đọc'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi đánh dấu thông báo đã đọc: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    public function markAllAsRead() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Đánh dấu tất cả đã đọc
            $result = $this->notificationService->markAllAsRead($userId);
            
            if (!$result) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không thể đánh dấu tất cả thông báo đã đọc'
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Đã đánh dấu tất cả thông báo đã đọc'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi đánh dấu tất cả thông báo đã đọc: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy số lượng thông báo chưa đọc
     */
    public function getUnreadCount() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Đếm số lượng thông báo chưa đọc
            $query = "SELECT COUNT(*) AS unread_count FROM notifications 
                     WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'unread_count' => (int)$unreadCount
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy số lượng thông báo chưa đọc: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cập nhật cài đặt thông báo
     */
    public function updateNotificationSettings() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['settings']) || !is_array($data['settings'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Cài đặt thông báo là bắt buộc'
                ]);
                return;
            }
            
            $settings = $data['settings'];
            
            // Cập nhật cài đặt
            $allowedSettings = [
                'email_notifications', 
                'push_notifications', 
                'review_reminders', 
                'achievement_notifications', 
                'social_notifications'
            ];
            
            $updateFields = [];
            $params = [];
            
            foreach ($settings as $key => $value) {
                if (in_array($key, $allowedSettings)) {
                    $updateFields[] = "$key = :$key";
                    $params[":$key"] = $value ? 1 : 0;
                }
            }
            
            if (empty($updateFields)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không có cài đặt hợp lệ để cập nhật'
                ]);
                return;
            }
            
            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            $stmt->bindParam(":user_id", $userId);
            $result = $stmt->execute();
            
            if (!$result) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không thể cập nhật cài đặt thông báo'
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Đã cập nhật cài đặt thông báo'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi cập nhật cài đặt thông báo: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy cài đặt thông báo
     */
    public function getNotificationSettings() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $query = "SELECT 
                      email_notifications, 
                      push_notifications, 
                      review_reminders, 
                      achievement_notifications, 
                      social_notifications 
                      FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không thể lấy cài đặt thông báo'
                ]);
                return;
            }
            
            // Chuyển đổi giá trị từ 0/1 sang boolean
            foreach ($settings as $key => $value) {
                $settings[$key] = (bool)$value;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $settings
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy cài đặt thông báo: ' . $e->getMessage()
            ]);
        }
    }
} 