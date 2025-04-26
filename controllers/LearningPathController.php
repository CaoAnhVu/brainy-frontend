<?php
require_once 'controllers/BaseController.php';
require_once 'services/LearningPathService.php';
require_once 'services/UserProgressService.php';

class LearningPathController extends BaseController {
    private $learningPathService;
    private $userProgressService;
    
    public function __construct() {
        parent::__construct();
        $this->learningPathService = new LearningPathService($this->conn);
        $this->userProgressService = new UserProgressService($this->conn);
    }
    
    /**
     * Tạo hoặc tải lộ trình học tập cho người dùng
     */
    public function getLearningPath() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Kiểm tra xem đã có lộ trình hay chưa
            $query = "SELECT * FROM learning_paths WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $path = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Nếu chưa có hoặc đã cũ (> 24h), tạo mới
            if (!$path || (time() - strtotime($path['created_at']) > 86400)) {
                $learningPath = $this->learningPathService->generateLearningPath($userId);
            } else {
                // Đã có lộ trình, chỉ cần parse JSON
                $learningPath = json_decode($path['path_data'], true);
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $learningPath
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy lộ trình học tập: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cập nhật mục tiêu học tập của người dùng
     */
    public function updateLearningGoals() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            $requiredFields = [
                'proficiency_level',
                'goal_type',
                'target_words_count',
                'available_time_minutes',
                'preferred_learning_time'
            ];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    $this->sendResponse(400, [
                        'success' => false,
                        'message' => "Thiếu trường $field"
                    ]);
                    return;
                }
            }
            
            // Validate proficiency_level
            $validProficiencyLevels = ['beginner', 'intermediate', 'advanced'];
            if (!in_array($data['proficiency_level'], $validProficiencyLevels)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => "Trình độ không hợp lệ"
                ]);
                return;
            }
            
            // Validate goal_type
            $validGoalTypes = ['casual', 'regular', 'intensive'];
            if (!in_array($data['goal_type'], $validGoalTypes)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => "Mục tiêu không hợp lệ"
                ]);
                return;
            }
            
            // Validate target_words_count
            if (!is_numeric($data['target_words_count']) || $data['target_words_count'] < 1) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => "Số lượng từ mục tiêu không hợp lệ"
                ]);
                return;
            }
            
            // Validate available_time_minutes
            if (!is_numeric($data['available_time_minutes']) || $data['available_time_minutes'] < 5) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => "Thời gian học không hợp lệ"
                ]);
                return;
            }
            
            $result = $this->learningPathService->updateLearningGoals($userId, $data);
            
            if (!$result) {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Không thể cập nhật mục tiêu học tập'
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Đã cập nhật mục tiêu học tập'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi cập nhật mục tiêu học tập: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy mục tiêu học tập của người dùng
     */
    public function getLearningGoals() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $goals = $this->learningPathService->getUserLearningGoals($userId);
            
            if (!$goals) {
                // Trả về mục tiêu mặc định nếu chưa có
                $this->sendResponse(200, [
                    'success' => true,
                    'data' => [
                        'proficiency_level' => 'beginner',
                        'goal_type' => 'regular',
                        'target_words_count' => 1000,
                        'available_time_minutes' => 30,
                        'preferred_learning_time' => 'evening'
                    ]
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $goals
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy mục tiêu học tập: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy thông tin tiến độ học tập tổng quan
     */
    public function getLearningProgress() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy tổng số từ đã học
            $query = "SELECT COUNT(*) AS count FROM user_notes 
                     WHERE user_id = :user_id AND status = 'learned'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $wordsLearned = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Lấy thông tin streak
            $streak = $this->userProgressService->getCurrentStreak($userId);
            
            // Lấy tiến độ theo từng danh mục
            $query = "SELECT c.id, c.name, c.description, 
                     (SELECT COUNT(*) FROM user_notes un 
                      JOIN words w ON un.word_id = w.id 
                      JOIN lessons l ON w.lesson_id = l.id 
                      WHERE l.category_id = c.id AND un.user_id = :user_id AND un.status = 'learned') AS learned_words,
                     (SELECT COUNT(*) FROM words w 
                      JOIN lessons l ON w.lesson_id = l.id 
                      WHERE l.category_id = c.id) AS total_words
                     FROM categories c
                     ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $categoryProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tính phần trăm hoàn thành cho mỗi danh mục
            foreach ($categoryProgress as &$category) {
                $category['progress_percent'] = $category['total_words'] > 0 
                    ? round(($category['learned_words'] / $category['total_words']) * 100) 
                    : 0;
            }
            
            // Lấy thống kê học tập trong 7 ngày gần đây
            $query = "SELECT 
                      DATE(created_at) AS date,
                      COUNT(*) AS words_learned
                      FROM user_notes
                      WHERE user_id = :user_id 
                      AND status = 'learned' 
                      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY date";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $dailyProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Trả về kết quả
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'total_words_learned' => (int)$wordsLearned,
                    'current_streak' => $streak,
                    'category_progress' => $categoryProgress,
                    'daily_progress' => $dailyProgress
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy tiến độ học tập: ' . $e->getMessage()
            ]);
        }
    }
} 