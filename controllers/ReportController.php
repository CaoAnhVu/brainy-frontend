<?php
require_once 'controllers/BaseController.php';
require_once 'services/ReportService.php';
require_once 'services/UserService.php';

class ReportController extends BaseController {
    private $reportService;
    private $userService;
    
    public function __construct() {
        parent::__construct();
        $this->reportService = new ReportService($this->conn);
        $this->userService = new UserService($this->conn);
    }
    
    /**
     * Tạo báo cáo học tập hàng tuần cho người dùng
     */
    public function generateWeeklyReport() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $report = $this->reportService->generateWeeklyReport($userId);
            
            if (!$report) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không thể tạo báo cáo học tập'
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $report
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Tạo báo cáo tiến độ theo danh mục
     */
    public function generateCategoryReport() {
        try {
            $userId = $this->getUserIdFromToken();
            
            if (!isset($_GET['categoryId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Cần cung cấp ID danh mục'
                ]);
                return;
            }
            
            $categoryId = $_GET['categoryId'];
            
            $report = $this->reportService->generateProgressReport($userId, $categoryId);
            
            if (!$report) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Không thể tạo báo cáo tiến độ cho danh mục này'
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $report
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo danh mục: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy báo cáo tổng quát về tiến độ học tập
     */
    public function getLearningOverview() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy thống kê tổng quan
            $query = "SELECT 
                        (SELECT COUNT(*) FROM user_notes WHERE user_id = :user_id AND status = 'learned') AS total_learned,
                        (SELECT COUNT(*) FROM review_sessions WHERE user_id = :user_id) AS total_reviews,
                        (SELECT SUM(duration) FROM learning_sessions WHERE user_id = :user_id) AS total_time,
                        (SELECT COUNT(*) FROM words) AS total_words";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Lấy 5 danh mục có tiến độ cao nhất
            $query = "SELECT c.id, c.name, c.description, 
                            COUNT(DISTINCT w.id) AS total_words,
                            COUNT(DISTINCT CASE WHEN un.status = 'learned' THEN w.id END) AS learned_words
                      FROM categories c
                      LEFT JOIN lessons l ON l.category_id = c.id
                      LEFT JOIN words w ON w.lesson_id = l.id
                      LEFT JOIN user_notes un ON un.word_id = w.id AND un.user_id = :user_id
                      GROUP BY c.id
                      ORDER BY (COUNT(DISTINCT CASE WHEN un.status = 'learned' THEN w.id END) / COUNT(DISTINCT w.id)) DESC
                      LIMIT 5";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $topCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tính tỷ lệ tiến độ cho mỗi danh mục
            foreach ($topCategories as &$category) {
                $category['progress'] = $category['total_words'] > 0 
                    ? round(($category['learned_words'] / $category['total_words']) * 100) 
                    : 0;
            }
            
            // Lấy thông tin streak học tập
            $currentStreak = $this->userService->getUserStreak($userId);
            
            $response = [
                'stats' => [
                    'total_learned' => (int)$stats['total_learned'],
                    'total_reviews' => (int)$stats['total_reviews'],
                    'total_time' => (int)$stats['total_time'], // Tổng thời gian học (phút)
                    'total_words' => (int)$stats['total_words'],
                    'progress_percent' => $stats['total_words'] > 0 
                        ? round(($stats['total_learned'] / $stats['total_words']) * 100) 
                        : 0,
                    'current_streak' => $currentStreak,
                ],
                'top_categories' => $topCategories,
                'recent_activity' => $this->getRecentActivity($userId)
            ];
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $response
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin tổng quan: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy hoạt động gần đây của người dùng
     */
    private function getRecentActivity($userId, $limit = 10) {
        try {
            $query = "
                (SELECT 'word_learned' as type, w.word as item_name, un.created_at as action_time
                FROM user_notes un
                JOIN words w ON w.id = un.word_id
                WHERE un.user_id = :user_id AND un.status = 'learned')
                
                UNION ALL
                
                (SELECT 'review_session' as type, CONCAT('Reviewed ', COUNT(*), ' words') as item_name, rs.completed_at as action_time
                FROM review_sessions rs
                WHERE rs.user_id = :user_id AND rs.completed_at IS NOT NULL)
                
                UNION ALL
                
                (SELECT 'lesson_completed' as type, l.title as item_name, lp.completed_at as action_time
                FROM lesson_progress lp
                JOIN lessons l ON l.id = lp.lesson_id
                WHERE lp.user_id = :user_id AND lp.status = 'completed')
                
                ORDER BY action_time DESC
                LIMIT :limit
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Lấy danh sách báo cáo tuần đã lưu
     */
    public function getSavedReports() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $query = "SELECT id, report_date, report_type, title 
                     FROM user_reports 
                     WHERE user_id = :user_id 
                     ORDER BY report_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $reports
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách báo cáo: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy chi tiết báo cáo đã lưu
     */
    public function getSavedReportDetail() {
        try {
            $userId = $this->getUserIdFromToken();
            
            if (!isset($_GET['reportId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Cần cung cấp ID báo cáo'
                ]);
                return;
            }
            
            $reportId = $_GET['reportId'];
            
            $query = "SELECT * FROM user_reports WHERE id = :id AND user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $reportId);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$report) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Không tìm thấy báo cáo'
                ]);
                return;
            }
            
            // Parse report_data JSON
            $report['report_data'] = json_decode($report['report_data'], true);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $report
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết báo cáo: ' . $e->getMessage()
            ]);
        }
    }
} 