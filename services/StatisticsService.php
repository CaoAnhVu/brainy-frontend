<?php
class StatisticsService {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getUserStatistics($userId) {
        // Thống kê tiến trình
        $query = "
            SELECT 
                status, 
                COUNT(*) as count 
            FROM 
                user_progress 
            WHERE 
                user_id = :user_id 
            GROUP BY 
                status";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        $progressStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Từ vựng đã học theo danh mục
        $query = "
            SELECT 
                c.id,
                c.title,
                COUNT(DISTINCT w.id) as total_words,
                COUNT(DISTINCT up.id) as learned_words
            FROM 
                categories c
                JOIN lessons l ON c.id = l.category_id
                JOIN words w ON l.id = w.lesson_id
                LEFT JOIN user_progress up ON w.id = up.word_id AND up.user_id = :user_id
            GROUP BY 
                c.id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tính toán tỷ lệ hoàn thành
        foreach ($categoryStats as &$category) {
            $category['completion_rate'] = $category['total_words'] > 0 
                ? round(($category['learned_words'] / $category['total_words']) * 100) 
                : 0;
        }
        
        return [
            'progress' => $progressStats,
            'categories' => $categoryStats
        ];
    }
    
    public function getUserLearningStats($userId) {
        // Thống kê tổng quát:
        // - Số từ đã học
        // - Số từ đã thuộc
        // - Thời gian học
        // - Streak days
    }
    
    public function getCategoryProgress($userId, $categoryId) {
        // Tiến độ học theo danh mục
    }
    
    public function getDailyStats($userId) {
        // Thống kê theo ngày
    }
}