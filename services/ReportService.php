<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/UserProgressService.php';

class ReportService extends BaseService {
    private $userService;
    private $userProgressService;
    
    public function __construct($db) {
        parent::__construct($db);
        $this->userService = new UserService($db);
        $this->userProgressService = new UserProgressService($db);
    }
    
    /**
     * Tạo báo cáo học tập hàng tuần cho người dùng
     * 
     * @param string $userId ID của người dùng
     * @return array Báo cáo học tập
     */
    public function generateWeeklyReport($userId) {
        // Lấy thông tin người dùng
        $user = $this->userService->read($userId);
        
        if (!$user) {
            return false;
        }
        
        // Xác định thời gian bắt đầu và kết thúc tuần
        $endDate = date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        // Lấy số lượng từ mới đã học trong tuần
        $newWordsLearned = $this->getNewWordsLearnedInPeriod($userId, $startDate, $endDate);
        
        // Lấy số lượng phiên ôn tập trong tuần
        $reviewSessions = $this->getReviewSessionsInPeriod($userId, $startDate, $endDate);
        
        // Lấy tổng thời gian học trong tuần (phút)
        $totalLearningTime = $this->getTotalLearningTimeInPeriod($userId, $startDate, $endDate);
        
        // Lấy tỷ lệ đúng trong các phiên ôn tập
        $reviewAccuracy = $this->getReviewAccuracyInPeriod($userId, $startDate, $endDate);
        
        // Lấy tiến độ theo danh mục
        $categoryProgress = $this->getCategoryProgressInPeriod($userId, $startDate, $endDate);
        
        // Tạo báo cáo
        $report = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'summary' => [
                'new_words_learned' => $newWordsLearned,
                'review_sessions' => count($reviewSessions),
                'total_learning_time' => $totalLearningTime,
                'review_accuracy' => $reviewAccuracy
            ],
            'category_progress' => $categoryProgress,
            'daily_activity' => $this->getDailyActivityInPeriod($userId, $startDate, $endDate),
            'streaks' => [
                'current_streak' => $this->userProgressService->getCurrentStreak($userId),
                'best_streak' => $this->userProgressService->getBestStreak($userId),
                'this_week_streaks' => $this->getStreaksInPeriod($userId, $startDate, $endDate)
            ]
        ];
        
        // Thêm đề xuất cải thiện
        $report['recommendations'] = $this->generateRecommendations($report);
        
        // Lưu báo cáo
        $this->saveWeeklyReport($userId, $report);
        
        return $report;
    }
    
    /**
     * Tạo báo cáo tiến độ theo danh mục
     * 
     * @param string $userId ID của người dùng
     * @param string $categoryId ID của danh mục
     * @return array Báo cáo tiến độ
     */
    public function generateProgressReport($userId, $categoryId) {
        // Lấy thông tin người dùng
        $user = $this->userService->read($userId);
        
        if (!$user) {
            return false;
        }
        
        // Lấy thông tin danh mục
        $query = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $categoryId);
        $stmt->execute();
        
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            return false;
        }
        
        // Lấy danh sách bài học trong danh mục
        $query = "SELECT * FROM lessons WHERE category_id = :category_id ORDER BY order_index ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category_id", $categoryId);
        $stmt->execute();
        
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy tiến độ của từng bài học
        $lessonProgress = [];
        
        foreach ($lessons as $lesson) {
            $lessonId = $lesson['id'];
            
            // Lấy tổng số từ trong bài học
            $query = "SELECT COUNT(*) AS total FROM words WHERE lesson_id = :lesson_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":lesson_id", $lessonId);
            $stmt->execute();
            
            $totalWords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Lấy số từ đã học
            $query = "SELECT COUNT(*) AS learned FROM user_notes 
                      WHERE user_id = :user_id AND word_id IN (
                          SELECT id FROM words WHERE lesson_id = :lesson_id
                      ) AND status = 'learned'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":lesson_id", $lessonId);
            $stmt->execute();
            
            $learnedWords = $stmt->fetch(PDO::FETCH_ASSOC)['learned'];
            
            // Tính tỷ lệ hoàn thành
            $progressPercent = $totalWords > 0 ? round(($learnedWords / $totalWords) * 100) : 0;
            
            $lessonProgress[] = [
                'lesson_id' => $lessonId,
                'lesson_title' => $lesson['title'],
                'total_words' => $totalWords,
                'learned_words' => $learnedWords,
                'progress_percent' => $progressPercent
            ];
        }
        
        // Tính tiến độ tổng thể của danh mục
        $totalWords = array_sum(array_column($lessonProgress, 'total_words'));
        $learnedWords = array_sum(array_column($lessonProgress, 'learned_words'));
        $overallProgress = $totalWords > 0 ? round(($learnedWords / $totalWords) * 100) : 0;
        
        // Tạo báo cáo
        $report = [
            'category' => $category,
            'overall_progress' => [
                'total_words' => $totalWords,
                'learned_words' => $learnedWords,
                'progress_percent' => $overallProgress
            ],
            'lesson_progress' => $lessonProgress,
            'recent_activity' => $this->getRecentActivityInCategory($userId, $categoryId, 10),
            'recommendations' => []
        ];
        
        // Thêm đề xuất
        if ($overallProgress < 20) {
            $report['recommendations'][] = "Bắt đầu học từ những bài học cơ bản nhất";
        } elseif ($overallProgress < 50) {
            $report['recommendations'][] = "Tiếp tục học thêm các từ mới";
        } elseif ($overallProgress < 80) {
            $report['recommendations'][] = "Tập trung ôn tập các từ đã học";
        } else {
            $report['recommendations'][] = "Hoàn thiện những từ cuối cùng và chuyển sang danh mục tiếp theo";
        }
        
        return $report;
    }
    
    /**
     * Lấy số lượng từ mới đã học trong khoảng thời gian
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return int Số lượng từ mới
     */
    private function getNewWordsLearnedInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT COUNT(*) AS total FROM user_notes 
                  WHERE user_id = :user_id 
                  AND status = 'learned' 
                  AND created_at BETWEEN :start_date AND :end_date";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }
    
    /**
     * Lấy danh sách phiên ôn tập trong khoảng thời gian
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return array Danh sách phiên ôn tập
     */
    private function getReviewSessionsInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT * FROM review_sessions 
                  WHERE user_id = :user_id 
                  AND created_at BETWEEN :start_date AND :end_date 
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy tổng thời gian học trong khoảng thời gian (phút)
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return int Tổng thời gian (phút)
     */
    private function getTotalLearningTimeInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT SUM(duration_minutes) AS total FROM learning_sessions 
                  WHERE user_id = :user_id 
                  AND created_at BETWEEN :start_date AND :end_date";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }
    
    /**
     * Lấy tỷ lệ đúng trong các phiên ôn tập
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return float Tỷ lệ đúng (0-100)
     */
    private function getReviewAccuracyInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT 
                  SUM(CASE WHEN result = 'correct' THEN 1 ELSE 0 END) AS correct_count,
                  COUNT(*) AS total_count
                  FROM review_results
                  WHERE user_id = :user_id
                  AND created_at BETWEEN :start_date AND :end_date";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['total_count'] == 0) {
            return 0;
        }
        
        return round(($result['correct_count'] / $result['total_count']) * 100, 2);
    }
    
    /**
     * Lấy tiến độ theo danh mục trong khoảng thời gian
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return array Tiến độ theo danh mục
     */
    private function getCategoryProgressInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT c.id, c.title, 
                  COUNT(DISTINCT w.id) AS total_words,
                  SUM(CASE WHEN un.status = 'learned' AND un.updated_at BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) AS new_learned_words,
                  SUM(CASE WHEN un.status = 'learned' THEN 1 ELSE 0 END) AS total_learned_words
                  FROM categories c
                  JOIN lessons l ON c.id = l.category_id
                  JOIN words w ON l.id = w.lesson_id
                  LEFT JOIN user_notes un ON w.id = un.word_id AND un.user_id = :user_id
                  GROUP BY c.id, c.title
                  HAVING new_learned_words > 0
                  ORDER BY new_learned_words DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính tỷ lệ hoàn thành
        foreach ($categories as &$category) {
            $category['progress_percent'] = $category['total_words'] > 0 
                ? round(($category['total_learned_words'] / $category['total_words']) * 100) 
                : 0;
            
            $category['new_progress_percent'] = $category['total_words'] > 0 
                ? round(($category['new_learned_words'] / $category['total_words']) * 100) 
                : 0;
        }
        
        return $categories;
    }
    
    /**
     * Lấy hoạt động học tập theo ngày
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return array Hoạt động theo ngày
     */
    private function getDailyActivityInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT 
                  DATE(created_at) AS date,
                  COUNT(DISTINCT CASE WHEN type = 'new_word' THEN entity_id END) AS new_words,
                  COUNT(DISTINCT CASE WHEN type = 'review' THEN entity_id END) AS reviews,
                  SUM(CASE WHEN type = 'learn_time' THEN value ELSE 0 END) AS learning_minutes
                  FROM user_activity_logs
                  WHERE user_id = :user_id
                  AND created_at BETWEEN :start_date AND :end_date
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy thông tin streak trong khoảng thời gian
     * 
     * @param string $userId ID của người dùng
     * @param string $startDate Thời gian bắt đầu
     * @param string $endDate Thời gian kết thúc
     * @return array Thông tin streak
     */
    private function getStreaksInPeriod($userId, $startDate, $endDate) {
        $query = "SELECT date, has_activity FROM user_daily_streaks
                  WHERE user_id = :user_id
                  AND date BETWEEN DATE(:start_date) AND DATE(:end_date)
                  ORDER BY date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":start_date", $startDate);
        $stmt->bindParam(":end_date", $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy hoạt động gần đây trong danh mục
     * 
     * @param string $userId ID của người dùng
     * @param string $categoryId ID của danh mục
     * @param int $limit Số lượng tối đa
     * @return array Danh sách hoạt động
     */
    private function getRecentActivityInCategory($userId, $categoryId, $limit = 10) {
        $query = "SELECT a.*, w.word
                  FROM user_activity_logs a
                  JOIN words w ON a.entity_id = w.id
                  JOIN lessons l ON w.lesson_id = l.id
                  WHERE a.user_id = :user_id
                  AND l.category_id = :category_id
                  ORDER BY a.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":category_id", $categoryId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Tạo đề xuất cải thiện dựa trên báo cáo
     * 
     * @param array $report Báo cáo học tập
     * @return array Danh sách đề xuất
     */
    private function generateRecommendations($report) {
        $recommendations = [];
        
        // Kiểm tra số từ mới đã học
        if ($report['summary']['new_words_learned'] < 10) {
            $recommendations[] = "Hãy cố gắng học ít nhất 10 từ mới mỗi tuần";
        }
        
        // Kiểm tra số phiên ôn tập
        if ($report['summary']['review_sessions'] < 5) {
            $recommendations[] = "Ôn tập thường xuyên hơn, ít nhất 5 phiên mỗi tuần";
        }
        
        // Kiểm tra tỷ lệ đúng
        if ($report['summary']['review_accuracy'] < 70) {
            $recommendations[] = "Tập trung ôn tập kỹ hơn để cải thiện tỷ lệ trả lời đúng";
        }
        
        // Kiểm tra streak
        if (count($report['streaks']['this_week_streaks']) < 5) {
            $recommendations[] = "Duy trì học tập đều đặn mỗi ngày để xây dựng thói quen";
        }
        
        return $recommendations;
    }
    
    /**
     * Lưu báo cáo tuần
     * 
     * @param string $userId ID của người dùng
     * @param array $report Báo cáo học tập
     * @return boolean Kết quả lưu
     */
    private function saveWeeklyReport($userId, $report) {
        $id = $this->generateUUID();
        $reportData = json_encode($report);
        
        $query = "INSERT INTO user_reports (id, user_id, type, report_data, created_at) 
                  VALUES (:id, :user_id, 'weekly', :report_data, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":report_data", $reportData);
        
        return $stmt->execute();
    }
}