<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/UserProgressService.php';

class LearningPathService extends BaseService {
    private $userService;
    private $userProgressService;
    
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "learning_paths";
        $this->userService = new UserService($db);
        $this->userProgressService = new UserProgressService($db);
    }
    
    /**
     * Tạo lộ trình học tập cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array Lộ trình học tập
     */
    public function generateLearningPath($userId) {
        // Lấy thông tin người dùng
        $user = $this->userService->read($userId);
        
        // Lấy tiến độ hiện tại
        $progress = $this->userProgressService->getUserProgress($userId);
        
        // Lấy mục tiêu học tập của người dùng
        $goals = $this->getUserLearningGoals($userId);
        
        // Xác định thời gian rảnh mỗi ngày
        $availableTimePerDay = $goals['available_time_minutes'] ?? 30; // Mặc định 30 phút/ngày
        
        // Xác định mục tiêu số từ mỗi ngày
        $targetWordsPerDay = min(
            ceil($availableTimePerDay / 5), // Ước tính 5 phút/từ
            20 // Giới hạn tối đa 20 từ/ngày
        );
        
        // Tính toán mục tiêu ôn tập
        $reviewWordsPerDay = min(
            $progress['total_words_learned'],
            ceil($targetWordsPerDay * 1.5)
        );
        
        // Lấy danh sách danh mục phù hợp với mục tiêu và trình độ
        $categories = $this->getRecommendedCategories($userId);
        
        // Tạo lộ trình học tập
        $learningPath = [
            'daily_target' => [
                'new_words' => $targetWordsPerDay,
                'review_words' => $reviewWordsPerDay,
                'total_time' => $availableTimePerDay
            ],
            'recommended_categories' => array_slice($categories, 0, 3),
            'next_lessons' => $this->getNextLessons($userId, 5),
            'review_sessions' => [
                'morning' => [
                    'time' => '08:00',
                    'words' => ceil($reviewWordsPerDay * 0.3)
                ],
                'afternoon' => [
                    'time' => '13:00',
                    'words' => ceil($reviewWordsPerDay * 0.3)
                ],
                'evening' => [
                    'time' => '20:00',
                    'words' => ceil($reviewWordsPerDay * 0.4)
                ]
            ]
        ];
        
        // Lưu lộ trình học tập
        $this->saveLearningPath($userId, $learningPath);
        
        return $learningPath;
    }
    
    /**
     * Cập nhật mục tiêu học tập của người dùng
     * 
     * @param string $userId ID của người dùng
     * @param array $goals Mục tiêu học tập
     * @return boolean Kết quả cập nhật
     */
    public function updateLearningGoals($userId, $goals) {
        // Kiểm tra xem đã có mục tiêu chưa
        $query = "SELECT id FROM user_learning_goals WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Cập nhật mục tiêu hiện có
            $query = "UPDATE user_learning_goals 
                      SET proficiency_level = :proficiency_level,
                          goal_type = :goal_type,
                          target_words_count = :target_words_count,
                          available_time_minutes = :available_time_minutes,
                          preferred_learning_time = :preferred_learning_time,
                          updated_at = NOW() 
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":proficiency_level", $goals['proficiency_level']);
            $stmt->bindParam(":goal_type", $goals['goal_type']);
            $stmt->bindParam(":target_words_count", $goals['target_words_count']);
            $stmt->bindParam(":available_time_minutes", $goals['available_time_minutes']);
            $stmt->bindParam(":preferred_learning_time", $goals['preferred_learning_time']);
            $stmt->bindParam(":id", $existing['id']);
            
            $success = $stmt->execute();
        } else {
            // Tạo mục tiêu mới
            $id = $this->generateUUID();
            
            $query = "INSERT INTO user_learning_goals 
                      (id, user_id, proficiency_level, goal_type, target_words_count, 
                      available_time_minutes, preferred_learning_time, created_at) 
                      VALUES 
                      (:id, :user_id, :proficiency_level, :goal_type, :target_words_count, 
                      :available_time_minutes, :preferred_learning_time, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":proficiency_level", $goals['proficiency_level']);
            $stmt->bindParam(":goal_type", $goals['goal_type']);
            $stmt->bindParam(":target_words_count", $goals['target_words_count']);
            $stmt->bindParam(":available_time_minutes", $goals['available_time_minutes']);
            $stmt->bindParam(":preferred_learning_time", $goals['preferred_learning_time']);
            
            $success = $stmt->execute();
        }
        
        if ($success) {
            // Tạo lại lộ trình học tập
            $this->generateLearningPath($userId);
        }
        
        return $success;
    }
    
    /**
     * Lấy mục tiêu học tập của người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array|false Mục tiêu học tập nếu tồn tại, false nếu không
     */
    public function getUserLearningGoals($userId) {
        $query = "SELECT * FROM user_learning_goals WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách danh mục được đề xuất
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách danh mục
     */
    private function getRecommendedCategories($userId) {
        $query = "SELECT c.*, 
                  COALESCE(up.progress, 0) AS progress 
                  FROM categories c 
                  LEFT JOIN user_progress up ON c.id = up.category_id AND up.user_id = :user_id 
                  ORDER BY 
                  CASE 
                    WHEN COALESCE(up.progress, 0) > 0 AND COALESCE(up.progress, 0) < 100 THEN 1 
                    WHEN COALESCE(up.progress, 0) = 0 THEN 2 
                    ELSE 3 
                  END, 
                  c.difficulty ASC, 
                  c.title ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy bài học tiếp theo cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @param int $limit Số bài học cần lấy
     * @return array Danh sách bài học
     */
    private function getNextLessons($userId, $limit = 5) {
        $query = "SELECT l.*, c.title AS category_title, 
                  COALESCE(up.progress, 0) AS progress 
                  FROM lessons l 
                  JOIN categories c ON l.category_id = c.id 
                  LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = :user_id 
                  ORDER BY 
                  CASE 
                    WHEN COALESCE(up.progress, 0) > 0 AND COALESCE(up.progress, 0) < 100 THEN 1 
                    WHEN COALESCE(up.progress, 0) = 0 THEN 2 
                    ELSE 3 
                  END, 
                  l.order_index ASC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lưu lộ trình học tập
     * 
     * @param string $userId ID của người dùng
     * @param array $learningPath Lộ trình học tập
     * @return boolean Kết quả lưu
     */
    private function saveLearningPath($userId, $learningPath) {
        // Kiểm tra xem đã có lộ trình chưa
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        $pathData = json_encode($learningPath);
        
        if ($existing) {
            // Cập nhật lộ trình hiện có
            $query = "UPDATE " . $this->table_name . " 
                      SET path_data = :path_data, 
                          updated_at = NOW() 
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":path_data", $pathData);
            $stmt->bindParam(":id", $existing['id']);
            
            return $stmt->execute();
        } else {
            // Tạo lộ trình mới
            $id = $this->generateUUID();
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (id, user_id, path_data, created_at) 
                      VALUES 
                      (:id, :user_id, :path_data, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":path_data", $pathData);
            
            return $stmt->execute();
        }
    }
}