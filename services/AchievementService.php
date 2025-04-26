<?php
require_once __DIR__ . '/BaseService.php';

class AchievementService extends BaseService {
    private $userProgressService;
    
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "achievements";
        $this->userProgressService = new UserProgressService($db);
    }
    
    /**
     * Kiểm tra và trao thành tích cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách thành tích mới đạt được
     */
    public function checkAndAwardAchievements($userId) {
        // Lấy tiến độ hiện tại của người dùng
        $userProgress = $this->userProgressService->getUserProgress($userId);
        
        // Lấy danh sách thành tích đã đạt được
        $userAchievements = $this->getUserAchievements($userId);
        $existingAchievementIds = array_column($userAchievements, 'achievement_id');
        
        // Lấy tất cả thành tích có thể đạt được
        $allAchievements = $this->getAllAchievements();
        
        $newAchievements = [];
        
        foreach ($allAchievements as $achievement) {
            // Bỏ qua nếu đã đạt được
            if (in_array($achievement['id'], $existingAchievementIds)) {
                continue;
            }
            
            // Kiểm tra điều kiện đạt thành tích
            $isAchieved = false;
            
            switch ($achievement['type']) {
                case 'words_learned':
                    $isAchieved = $userProgress['total_words_learned'] >= $achievement['target_value'];
                    break;
                    
                case 'streak_days':
                    $isAchieved = $userProgress['current_streak'] >= $achievement['target_value'];
                    break;
                    
                case 'perfect_reviews':
                    $isAchieved = $userProgress['perfect_reviews'] >= $achievement['target_value'];
                    break;
                    
                case 'categories_completed':
                    $isAchieved = $userProgress['completed_categories'] >= $achievement['target_value'];
                    break;
            }
            
            if ($isAchieved) {
                // Thêm thành tích mới
                $this->awardAchievement($userId, $achievement['id']);
                $newAchievements[] = $achievement;
            }
        }
        
        return $newAchievements;
    }
    
    /**
     * Lấy danh sách thành tích của người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách thành tích
     */
    public function getUserAchievements($userId) {
        $query = "SELECT ua.*, a.name, a.description, a.type, a.target_value, a.icon 
                  FROM user_achievements ua 
                  JOIN achievements a ON ua.achievement_id = a.id 
                  WHERE ua.user_id = :user_id 
                  ORDER BY ua.achieved_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy tất cả thành tích có thể đạt được
     * 
     * @return array Danh sách thành tích
     */
    public function getAllAchievements() {
        $query = "SELECT * FROM achievements ORDER BY type, target_value";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Trao thành tích cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @param string $achievementId ID của thành tích
     * @return boolean Kết quả thực hiện
     */
    private function awardAchievement($userId, $achievementId) {
        $id = $this->generateUUID();
        
        $query = "INSERT INTO user_achievements (id, user_id, achievement_id, achieved_at) 
                  VALUES (:id, :user_id, :achievement_id, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":achievement_id", $achievementId);
        
        if ($stmt->execute()) {
            // Gửi thông báo về thành tích mới
            try {
                $notificationService = new NotificationService($this->conn);
                $notificationService->sendAchievementNotification($userId, $achievementId);
            } catch (Exception $e) {
                error_log("Error sending achievement notification: " . $e->getMessage());
            }
            
            return true;
        }
        
        return false;
    }
}