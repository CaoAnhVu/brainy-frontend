<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/EmailService.php';

class NotificationService extends BaseService {
    private $emailService;
    
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "notifications";
        $this->emailService = new EmailService($db);
    }
    
    /**
     * Gửi thông báo nhắc nhở ôn tập
     * 
     * @param string $userId ID của người dùng
     * @param array $reviewInfo Thông tin ôn tập
     * @return boolean Kết quả gửi
     */
    public function sendReviewReminder($userId) {
        // Lấy thông tin người dùng
        $userService = new UserService($this->conn);
        $user = $userService->read($userId);
        
        if (!$user) {
            return false;
        }
        
        // Lấy thông tin từ vựng cần ôn tập
        $reviewService = new ReviewService($this->conn);
        $pendingReviews = $reviewService->getPendingReviews($userId);
        
        if (empty($pendingReviews)) {
            return false; // Không có từ cần ôn tập
        }
        
        // Tạo nội dung thông báo
        $reviewCount = count($pendingReviews);
        $title = "Nhắc nhở ôn tập: {$reviewCount} từ đang chờ bạn";
        $body = "Bạn có {$reviewCount} từ cần ôn tập hôm nay. Hãy dành ít phút để giữ vững tiến độ học tập!";
        
        // Lưu thông báo vào database
        $notificationId = $this->createNotification($userId, 'review_reminder', $title, $body, [
            'review_count' => $reviewCount,
            'review_ids' => array_column($pendingReviews, 'id')
        ]);
        
        // Gửi email nếu người dùng đăng ký nhận thông báo qua email
        if ($user['email_notifications']) {
            $this->emailService->sendEmail(
                $user['email'],
                $title,
                $this->generateReviewReminderEmail($user, $pendingReviews)
            );
        }
        
        // Gửi push notification nếu có
        $this->sendPushNotification($userId, $title, $body);
        
        return $notificationId ? true : false;
    }
    
    /**
     * Gửi thông báo đạt thành tích mới
     * 
     * @param string $userId ID của người dùng
     * @param string $achievementId ID của thành tích
     * @return boolean Kết quả gửi
     */
    public function sendAchievementNotification($userId, $achievementId) {
        // Lấy thông tin người dùng
        $userService = new UserService($this->conn);
        $user = $userService->read($userId);
        
        if (!$user) {
            return false;
        }
        
        // Lấy thông tin thành tích
        $query = "SELECT * FROM achievements WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $achievementId);
        $stmt->execute();
        
        $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$achievement) {
            return false;
        }
        
        // Tạo nội dung thông báo
        $title = "Chúc mừng! Bạn đã đạt thành tích mới";
        $body = "Bạn đã đạt được thành tích \"{$achievement['name']}\". {$achievement['description']}";
        
        // Lưu thông báo vào database
        $notificationId = $this->createNotification($userId, 'achievement', $title, $body, [
            'achievement_id' => $achievementId,
            'achievement_name' => $achievement['name'],
            'achievement_icon' => $achievement['icon']
        ]);
        
        // Gửi email nếu người dùng đăng ký nhận thông báo qua email
        if ($user['email_notifications']) {
            $this->emailService->sendEmail(
                $user['email'],
                $title,
                $this->generateAchievementEmail($user, $achievement)
            );
        }
        
        // Gửi push notification nếu có
        $this->sendPushNotification($userId, $title, $body);
        
        return $notificationId ? true : false;
    }
    
    /**
     * Tạo thông báo mới
     * 
     * @param string $userId ID của người dùng
     * @param string $type Loại thông báo
     * @param string $title Tiêu đề
     * @param string $body Nội dung
     * @param array $data Dữ liệu bổ sung
     * @return string|false ID của thông báo nếu thành công, false nếu thất bại
     */
    public function createNotification($userId, $type, $title, $body, $data = []) {
        $id = $this->generateUUID();
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (id, user_id, type, title, body, data, is_read, created_at) 
                  VALUES 
                  (:id, :user_id, :type, :title, :body, :data, 0, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":body", $body);
        
        $jsonData = json_encode($data);
        $stmt->bindParam(":data", $jsonData);
        
        return $stmt->execute() ? $id : false;
    }
    
    /**
     * Lấy danh sách thông báo của người dùng
     * 
     * @param string $userId ID của người dùng
     * @param int $limit Số lượng thông báo tối đa
     * @param int $offset Vị trí bắt đầu
     * @return array Danh sách thông báo
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Chuyển đổi data từ JSON sang array
        foreach ($notifications as &$notification) {
            if (isset($notification['data'])) {
                $notification['data'] = json_decode($notification['data'], true);
            }
        }
        
        return $notifications;
    }
    
    /**
     * Đánh dấu thông báo đã đọc
     * 
     * @param string $notificationId ID của thông báo
     * @param string $userId ID của người dùng (để kiểm tra quyền)
     * @return boolean Kết quả cập nhật
     */
    public function markAsRead($notificationId, $userId) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $notificationId);
        $stmt->bindParam(":user_id", $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Đánh dấu tất cả thông báo đã đọc
     * 
     * @param string $userId ID của người dùng
     * @return int Số thông báo đã cập nhật
     */
    public function markAllAsRead($userId) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Tạo nội dung email nhắc nhở ôn tập
     * 
     * @param array $user Thông tin người dùng
     * @param array $pendingReviews Danh sách từ cần ôn tập
     * @return string Nội dung email dạng HTML
     */
    private function generateReviewReminderEmail($user, $pendingReviews) {
        $wordCount = count($pendingReviews);
        $sampleWords = array_slice($pendingReviews, 0, 5);
        $sampleWordsList = '';
        
        foreach ($sampleWords as $word) {
            $sampleWordsList .= "<li><strong>{$word['word']}</strong> - {$word['meaning']}</li>";
        }
        
        if ($wordCount > 5) {
            $sampleWordsList .= "<li>... và " . ($wordCount - 5) . " từ khác</li>";
        }
        
        return "
        <html>
        <head>
            <title>Nhắc nhở ôn tập</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2>Đã đến giờ ôn tập rồi!</h2>
                <p>Xin chào {$user['full_name']},</p>
                <p>Bạn có <strong>{$wordCount} từ</strong> cần ôn tập hôm nay. Hãy dành ít phút để giữ vững tiến độ học tập!</p>
                <h3>Danh sách từ cần ôn tập:</h3>
                <ul>
                    {$sampleWordsList}
                </ul>
                <p><a href='https://brainy.example.com/review' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Ôn tập ngay</a></p>
                <p>Hãy nhớ rằng, ôn tập thường xuyên là chìa khóa để ghi nhớ từ vựng lâu dài!</p>
                <p>Chúc bạn học tập hiệu quả,<br>Đội ngũ Brainy</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Tạo nội dung email thông báo thành tích
     * 
     * @param array $user Thông tin người dùng
     * @param array $achievement Thông tin thành tích
     * @return string Nội dung email dạng HTML
     */
    private function generateAchievementEmail($user, $achievement) {
        return "
        <html>
        <head>
            <title>Thành tích mới</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2>Chúc mừng! Bạn đã đạt được thành tích mới</h2>
                <p>Xin chào {$user['full_name']},</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <img src='{$achievement['icon']}' alt='{$achievement['name']}' style='width: 100px; height: 100px;'>
                    <h3>{$achievement['name']}</h3>
                    <p>{$achievement['description']}</p>
                </div>
                <p>Đây là một cột mốc đáng nhớ trong hành trình học tập của bạn. Hãy tiếp tục phát huy!</p>
                <p><a href='https://brainy.example.com/achievements' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Xem tất cả thành tích</a></p>
                <p>Trân trọng,<br>Đội ngũ Brainy</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Gửi push notification
     * 
     * @param string $userId ID của người dùng
     * @param string $title Tiêu đề
     * @param string $body Nội dung
     * @return boolean Kết quả gửi
     */
    private function sendPushNotification($userId, $title, $body) {
        // Lấy danh sách device tokens của người dùng
        $query = "SELECT device_token, platform FROM user_devices 
                  WHERE user_id = :user_id AND device_token IS NOT NULL AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($devices)) {
            return false; // Không có thiết bị nào đăng ký
        }
        
        // Nhóm các thiết bị theo platform
        $androidTokens = [];
        $iosTokens = [];
        $webTokens = [];
        
        foreach ($devices as $device) {
            switch ($device['platform']) {
                case 'android':
                    $androidTokens[] = $device['device_token'];
                    break;
                case 'ios':
                    $iosTokens[] = $device['device_token'];
                    break;
                case 'web':
                    $webTokens[] = $device['device_token'];
                    break;
            }
        }
        
        // Trong thực tế, bạn sẽ sử dụng Firebase Cloud Messaging hoặc một dịch vụ tương tự
        // Đây chỉ là triển khai mẫu
        if (!empty($androidTokens)) {
            $this->sendFcmNotification($androidTokens, $title, $body);
        }
        
        if (!empty($iosTokens)) {
            $this->sendApnsNotification($iosTokens, $title, $body);
        }
        
        if (!empty($webTokens)) {
            $this->sendWebPushNotification($webTokens, $title, $body);
        }
        
        return true;
    }
    
    /**
     * Gửi thông báo qua Firebase Cloud Messaging
     * 
     * @param array $tokens Danh sách device tokens
     * @param string $title Tiêu đề
     * @param string $body Nội dung
     * @return boolean Kết quả gửi
     */
    private function sendFcmNotification($tokens, $title, $body) {
        // Triển khai thực tế sẽ sử dụng FCM API
        // Đây chỉ là mã mẫu
        return true;
    }
    
    /**
     * Gửi thông báo qua Apple Push Notification Service
     * 
     * @param array $tokens Danh sách device tokens
     * @param string $title Tiêu đề
     * @param string $body Nội dung
     * @return boolean Kết quả gửi
     */
    private function sendApnsNotification($tokens, $title, $body) {
        // Triển khai thực tế sẽ sử dụng APNs API
        // Đây chỉ là mã mẫu
        return true;
    }
    
    /**
     * Gửi thông báo qua Web Push
     * 
     * @param array $tokens Danh sách device tokens
     * @param string $title Tiêu đề
     * @param string $body Nội dung
     * @return boolean Kết quả gửi
     */
    private function sendWebPushNotification($tokens, $title, $body) {
        // Triển khai thực tế sẽ sử dụng Web Push API
        // Đây chỉ là mã mẫu
        return true;
    }
}