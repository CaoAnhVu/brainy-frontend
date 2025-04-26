<?php
require_once __DIR__ . '/BaseService.php';

class UserProgressService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "user_progress";
    }

    public function getUserProgress($userId) {
        $query = "
            SELECT up.*, w.word, l.title as lesson_title, c.title as category_title
            FROM " . $this->table_name . " up
            JOIN words w ON up.word_id = w.id
            LEFT JOIN lessons l ON w.lesson_id = l.id
            LEFT JOIN categories c ON l.category_id = c.id
            WHERE up.user_id = :user_id
            ORDER BY up.last_review DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateProgress($userId, $wordId, $status) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND word_id = :word_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":word_id", $wordId);
        $stmt->execute();
        
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        $now = date('Y-m-d H:i:s');
        
        // Tính toán thời gian ôn tập tiếp theo dựa trên thuật toán Spaced Repetition
        $nextReview = $this->calculateNextReview($status, $progress ? $progress['review_count'] : 0);
        
        if ($progress) {
            return $this->update($progress['id'], [
                'status' => $status,
                'last_review' => $now,
                'next_review' => $nextReview,
                'review_count' => $progress['review_count'] + 1
            ]);
        } else {
            return $this->create([
                'user_id' => $userId,
                'word_id' => $wordId,
                'status' => $status,
                'last_review' => $now,
                'next_review' => $nextReview,
                'review_count' => 1
            ]);
        }
    }
    
    private function calculateNextReview($status, $reviewCount) {
        // Thuật toán Spaced Repetition đơn giản
        $hours = 0;
        
        switch ($status) {
            case 'new':
                $hours = 1;
                break;
                
            case 'learning':
                // 1 ngày, 3 ngày, 7 ngày, ...
                $hours = pow(2, min($reviewCount, 5)) * 24;
                break;
                
            case 'mastered':
                // 7 ngày, 14 ngày, 30 ngày, ...
                $hours = (7 * pow(2, min($reviewCount, 3))) * 24;
                break;
        }
        
        return date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
    }

    public function getStudyStats($userId) {
        // Thống kê học tập
    }

    public function getNextReviewWords($userId) {
        // Lấy danh sách từ cần ôn tập
    }
}