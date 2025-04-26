<?php
require_once 'BaseService.php';

class FlashcardService extends BaseService {
    public function __construct($db) {
        parent::__construct($db, 'flashcards');
    }
    
    public function createFlashcardForUser($userId, $wordId) {
        $flashcardId = $this->generateUUID();
        $data = [
            'id' => $flashcardId,
            'user_id' => $userId,
            'word_id' => $wordId,
            'status' => 'new',
            'next_review' => date('Y-m-d H:i:s') // Set to now for immediate review
        ];
        
        $this->create($data);
        return $flashcardId;
    }
    
    public function createFlashcardsForTopic($userId, $topicName) {
        // Lấy danh sách từ của topic
        $sql = "SELECT id FROM words WHERE id IN (
                SELECT word_id FROM topic_words WHERE topic = ?
            )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $topicName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $count = 0;
        while ($row = $result->fetch_assoc()) {
            $this->createFlashcardForUser($userId, $row['id']);
            $count++;
        }
        
        return $count;
    }
    
    public function getDueFlashcards($userId, $limit = 20) {
        $sql = "SELECT f.*, w.word, w.pos, w.phonetic, w.image_url, w.audio_url, 
                       s.definition, s.example
                FROM flashcards f
                JOIN words w ON f.word_id = w.id
                JOIN senses s ON w.id = s.word_id
                WHERE f.user_id = ? 
                AND (f.next_review IS NULL OR f.next_review <= NOW())
                ORDER BY f.status, f.next_review
                LIMIT ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function updateFlashcardAfterReview($flashcardId, $quality) {
        // Spaced Repetition Algorithm (SM-2)
        $sql = "SELECT * FROM flashcards WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $flashcardId);
        $stmt->execute();
        $flashcard = $stmt->get_result()->fetch_assoc();
        
        if (!$flashcard) {
            return false;
        }
        
        // Tính toán ease factor và interval mới
        $easeFactor = $flashcard['ease_factor'];
        $interval = $flashcard['learn_interval']; // Đã đổi tên từ interval thành learn_interval
        $status = $flashcard['status'];
        $reviewCount = $flashcard['review_count'] + 1;
        
        // Cập nhật ease factor dựa trên chất lượng nhớ
        $newEaseFactor = max(1.3, $easeFactor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02)));
        
        // Tính toán khoảng thời gian mới
        if ($quality < 3) {
            // Nếu chất lượng thấp (không nhớ tốt), reset về ban đầu
            $newInterval = 1;
            $status = 'learning';
        } else {
            if ($interval == 0) {
                $newInterval = 1;
            } else if ($interval == 1) {
                $newInterval = 6;
            } else {
                $newInterval = round($interval * $newEaseFactor);
            }
            
            if ($quality >= 4) {
                $status = 'known';
            } else {
                $status = 'review';
            }
        }
        
        // Tính toán thời gian review tiếp theo
        $nextReview = date('Y-m-d H:i:s', strtotime("+{$newInterval} days"));
        
        // Cập nhật flashcard
        $updateSql = "UPDATE flashcards 
                     SET ease_factor = ?, 
                         learn_interval = ?, 
                         status = ?,
                         next_review = ?,
                         review_count = ?
                     WHERE id = ?";
                     
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->bind_param("disssi", $newEaseFactor, $newInterval, $status, $nextReview, $reviewCount, $flashcardId);
        $result = $updateStmt->execute();
        
        // Log the review
        $reviewId = $this->generateUUID();
        $userId = $flashcard['user_id'];
        
        $logSql = "INSERT INTO review_logs (id, flashcard_id, user_id, quality) 
                  VALUES (?, ?, ?, ?)";
        $logStmt = $this->db->prepare($logSql);
        $logStmt->bind_param("sssi", $reviewId, $flashcardId, $userId, $quality);
        $logStmt->execute();
        
        return $result;
    }
    
    public function getFlashcardStats($userId) {
        $sql = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
                  SUM(CASE WHEN status = 'learning' THEN 1 ELSE 0 END) as learning,
                  SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as review,
                  SUM(CASE WHEN status = 'known' THEN 1 ELSE 0 END) as known
                FROM flashcards
                WHERE user_id = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}