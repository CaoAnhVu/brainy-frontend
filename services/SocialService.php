<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';

class SocialService extends BaseService {
    private $userService;
    
    public function __construct($db) {
        parent::__construct($db);
        $this->userService = new UserService($db);
    }
    
    /**
     * Thêm bạn bè
     * 
     * @param string $userId ID của người dùng
     * @param string $friendId ID của bạn bè
     * @return boolean Kết quả thực hiện
     */
    public function addFriend($userId, $friendId) {
        // Kiểm tra xem đã là bạn bè chưa
        if ($this->isFriend($userId, $friendId)) {
            return true; // Đã là bạn bè
        }
        
        // Thêm mối quan hệ bạn bè (2 chiều)
        $friendshipId = $this->generateUUID();
        
        $query = "INSERT INTO user_friends 
                  (id, user_id, friend_id, status, created_at) 
                  VALUES (:id, :user_id, :friend_id, 'pending', NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $friendshipId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":friend_id", $friendId);
        
        return $stmt->execute();
    }
    
    /**
     * Chấp nhận lời mời kết bạn
     * 
     * @param string $userId ID của người dùng
     * @param string $friendId ID của bạn bè
     * @return boolean Kết quả thực hiện
     */
    public function acceptFriendRequest($userId, $friendId) {
        // Kiểm tra xem có lời mời không
        $query = "SELECT * FROM user_friends 
                  WHERE user_id = :friend_id AND friend_id = :user_id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":friend_id", $friendId);
        $stmt->execute();
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return false; // Không có lời mời
        }
        
        // Cập nhật trạng thái lời mời
        $query = "UPDATE user_friends 
                  SET status = 'accepted', updated_at = NOW() 
                  WHERE user_id = :friend_id AND friend_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":friend_id", $friendId);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Tạo mối quan hệ ngược lại
        $friendshipId = $this->generateUUID();
        
        $query = "INSERT INTO user_friends 
                  (id, user_id, friend_id, status, created_at) 
                  VALUES (:id, :user_id, :friend_id, 'accepted', NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $friendshipId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":friend_id", $friendId);
        
        return $stmt->execute();
    }
    
    /**
     * Kiểm tra xem hai người dùng có phải bạn bè không
     * 
     * @param string $userId ID của người dùng
     * @param string $friendId ID của bạn bè
     * @return boolean Kết quả kiểm tra
     */
    public function isFriend($userId, $friendId) {
        $query = "SELECT * FROM user_friends 
                  WHERE user_id = :user_id AND friend_id = :friend_id AND status = 'accepted'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":friend_id", $friendId);
        $stmt->execute();
        
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách bạn bè
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách bạn bè
     */
    public function getFriends($userId) {
        $query = "SELECT u.*, uf.created_at as friendship_date 
                  FROM user_friends uf 
                  JOIN users u ON uf.friend_id = u.id 
                  WHERE uf.user_id = :user_id AND uf.status = 'accepted' 
                  ORDER BY u.full_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy bảng xếp hạng bạn bè
     * 
     * @param string $userId ID của người dùng
     * @param string $period Khoảng thời gian (week/month/all)
     * @return array Bảng xếp hạng
     */
    public function getFriendLeaderboard($userId, $period = 'week') {
        $friends = $this->getFriends($userId);
        $friendIds = array_column($friends, 'id');
        $friendIds[] = $userId; // Thêm cả người dùng hiện tại
        
        $friendIdsStr = "'" . implode("','", $friendIds) . "'";
        
        $dateCondition = "";
        switch ($period) {
            case 'week':
                $dateCondition = "AND logs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND logs.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                $dateCondition = "";
                break;
        }
        
        $query = "SELECT 
                  u.id, u.username, u.full_name, u.avatar_url,
                  COUNT(DISTINCT CASE WHEN logs.type = 'new_word' THEN logs.entity_id END) AS new_words_count,
                  COUNT(DISTINCT CASE WHEN logs.type = 'review' THEN logs.entity_id END) AS reviews_count,
                  SUM(CASE WHEN logs.type = 'learn_time' THEN logs.value ELSE 0 END) AS learning_minutes,
                  (SELECT MAX(current_streak) FROM user_progress WHERE user_id = u.id) AS current_streak
                  FROM users u
                  LEFT JOIN user_activity_logs logs ON u.id = logs.user_id {$dateCondition}
                  WHERE u.id IN ({$friendIdsStr})
                  GROUP BY u.id, u.username, u.full_name, u.avatar_url
                  ORDER BY new_words_count DESC, reviews_count DESC, learning_minutes DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Thêm thông tin xếp hạng
        foreach ($results as $index => &$result) {
            $result['rank'] = $index + 1;
            $result['is_current_user'] = ($result['id'] === $userId);
        }
        
        return $results;
    }
    
    /**
     * Chia sẻ tiến độ học tập
     * 
     * @param string $userId ID của người dùng
     * @param array $progress Thông tin tiến độ
     * @return string|false ID của bài đăng nếu thành công, false nếu thất bại
     */
    public function shareProgress($userId, $progress) {
        $postId = $this->generateUUID();
        
        $query = "INSERT INTO social_posts 
                  (id, user_id, type, content, data, created_at) 
                  VALUES (:id, :user_id, 'progress', :content, :data, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $postId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":content", $progress['message']);
        
        $progressData = json_encode([
            'words_learned' => $progress['words_learned'],
            'streak_days' => $progress['streak_days'],
            'category' => $progress['category'],
            'achievement' => $progress['achievement'] ?? null
        ]);
        
        $stmt->bindParam(":data", $progressData);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Thông báo cho bạn bè
        $this->notifyFriends($userId, $postId, 'progress_share');
        
        return $postId;
    }
    
    /**
     * Thông báo cho bạn bè
     * 
     * @param string $userId ID của người dùng
     * @param string $entityId ID của đối tượng
     * @param string $type Loại thông báo
     * @return boolean Kết quả thực hiện
     */
    private function notifyFriends($userId, $entityId, $type) {
        $query = "SELECT friend_id FROM user_friends 
                  WHERE user_id = :user_id AND status = 'accepted'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $friends = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($friends)) {
            return true; // Không có bạn bè để thông báo
        }
        
        // Lấy thông tin người dùng
        $user = $this->userService->read($userId);
        
        // Tạo nội dung thông báo
        $title = '';
        $body = '';
        
        switch ($type) {
            case 'progress_share':
                $title = "Cập nhật từ {$user['full_name']}";
                $body = "{$user['full_name']} vừa chia sẻ tiến độ học tập mới";
                break;
            case 'achievement':
                $title = "{$user['full_name']} đạt thành tích mới";
                $body = "{$user['full_name']} vừa đạt được một thành tích mới";
                break;
        }
        
        // Tạo thông báo cho từng người bạn
        foreach ($friends as $friendId) {
            $notificationId = $this->generateUUID();
            
            $query = "INSERT INTO notifications 
                      (id, user_id, type, title, body, data, is_read, created_at) 
                      VALUES (:id, :user_id, :type, :title, :body, :data, 0, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $notificationId);
            $stmt->bindParam(":user_id", $friendId);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":body", $body);
            
            $data = json_encode([
                'sender_id' => $userId,
                'sender_name' => $user['full_name'],
                'entity_id' => $entityId
            ]);
            
            $stmt->bindParam(":data", $data);
            $stmt->execute();
        }
        
        return true;
    }
    
    /**
     * Lấy dòng thời gian xã hội
     * 
     * @param string $userId ID của người dùng
     * @param int $limit Số lượng tối đa
     * @param int $offset Vị trí bắt đầu
     * @return array Danh sách bài đăng
     */
    public function getSocialFeed($userId, $limit = 20, $offset = 0) {
        $query = "SELECT p.*, u.username, u.full_name, u.avatar_url
                  FROM social_posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id IN (
                      SELECT friend_id FROM user_friends 
                      WHERE user_id = :user_id AND status = 'accepted'
                      UNION
                      SELECT :user_id
                  )
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Xử lý dữ liệu của mỗi bài đăng
        foreach ($posts as &$post) {
            if ($post['data']) {
                $post['data'] = json_decode($post['data'], true);
            }
            
            // Lấy số lượng like và comment
            $post['likes_count'] = $this->getPostLikesCount($post['id']);
            $post['comments_count'] = $this->getPostCommentsCount($post['id']);
            $post['is_liked'] = $this->isPostLiked($post['id'], $userId);
        }
        
        return $posts;
    }
    
    /**
     * Lấy số lượng like của bài đăng
     * 
     * @param string $postId ID của bài đăng
     * @return int Số lượng like
     */
    private function getPostLikesCount($postId) {
        $query = "SELECT COUNT(*) AS count FROM social_post_likes WHERE post_id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $postId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Lấy số lượng comment của bài đăng
     * 
     * @param string $postId ID của bài đăng
     * @return int Số lượng comment
     */
    private function getPostCommentsCount($postId) {
        $query = "SELECT COUNT(*) AS count FROM social_post_comments WHERE post_id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $postId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Kiểm tra xem người dùng đã like bài đăng chưa
     * 
     * @param string $postId ID của bài đăng
     * @param string $userId ID của người dùng
     * @return boolean Kết quả kiểm tra
     */
    private function isPostLiked($postId, $userId) {
        $query = "SELECT * FROM social_post_likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $postId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}