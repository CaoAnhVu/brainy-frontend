<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';

class ReviewService extends BaseService {
    private $userService;
    
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "review_sessions";
        $this->userService = new UserService($db);
    }
    
    /**
     * Tạo phiên ôn tập mới với danh sách từ cần ôn tập
     * 
     * @param string $userId ID của người dùng
     * @param int $limit Số lượng từ tối đa
     * @return array Thông tin phiên ôn tập
     */
    public function generateReviewSession($userId, $limit = 20) {
        // Lấy danh sách từ cần ôn tập
        $wordsToReview = $this->getWordsForReview($userId, $limit);
        
        if (empty($wordsToReview)) {
            return false; // Không có từ cần ôn tập
        }
        
        // Tạo phiên ôn tập mới
        $sessionId = $this->generateUUID();
        $wordIds = array_column($wordsToReview, 'id');
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (id, user_id, words_count, status, created_at) 
                  VALUES (:id, :user_id, :words_count, 'active', NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $sessionId);
        $stmt->bindParam(":user_id", $userId);
        $wordsCount = count($wordIds);
        $stmt->bindParam(":words_count", $wordsCount);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Thêm các từ vào phiên ôn tập
        foreach ($wordIds as $index => $wordId) {
            $query = "INSERT INTO session_words 
                      (session_id, word_id, position) 
                      VALUES (:session_id, :word_id, :position)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":session_id", $sessionId);
            $stmt->bindParam(":word_id", $wordId);
            $stmt->bindParam(":position", $index);
            $stmt->execute();
        }
        
        return [
            'session_id' => $sessionId,
            'words' => $wordsToReview,
            'total_words' => $wordsCount
        ];
    }
    
    /**
     * Lưu kết quả ôn tập
     * 
     * @param string $userId ID của người dùng
     * @param string $wordId ID của từ
     * @param string $result Kết quả (correct/incorrect)
     * @return boolean Kết quả lưu
     */
    public function submitReviewResult($userId, $wordId, $result) {
        // Lấy thông tin từ
        $query = "SELECT * FROM words WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $wordId);
        $stmt->execute();
        
        $word = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$word) {
            return false;
        }
        
        // Lấy thông tin note của người dùng
        $query = "SELECT * FROM user_notes 
                  WHERE user_id = :user_id AND word_id = :word_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":word_id", $wordId);
        $stmt->execute();
        
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu chưa có note, tạo mới
        if (!$note) {
            $noteId = $this->generateUUID();
            $query = "INSERT INTO user_notes 
                      (id, user_id, word_id, status, created_at) 
                      VALUES (:id, :user_id, :word_id, 'learning', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $noteId);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":word_id", $wordId);
            $stmt->execute();
            
            $query = "SELECT * FROM user_notes 
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $noteId);
            $stmt->execute();
            
            $note = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Cập nhật thông tin SRS (Spaced Repetition System)
        $newSrsLevel = $this->calculateNewSrsLevel($note, $result);
        $nextReviewDate = $this->calculateNextReviewDate($newSrsLevel);
        
        // Cập nhật note
        $query = "UPDATE user_notes 
                  SET srs_level = :srs_level, 
                      next_review_date = :next_review_date, 
                      last_review_date = NOW(), 
                      updated_at = NOW() 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":srs_level", $newSrsLevel);
        $stmt->bindParam(":next_review_date", $nextReviewDate);
        $stmt->bindParam(":id", $note['id']);
        
        if (!$stmt->execute()) {
            return false;
        }
        
        // Lưu kết quả ôn tập
        $resultId = $this->generateUUID();
        $query = "INSERT INTO review_results 
                  (id, user_id, word_id, result, created_at) 
                  VALUES (:id, :user_id, :word_id, :result, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $resultId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":word_id", $wordId);
        $stmt->bindParam(":result", $result);
        
        return $stmt->execute();
    }
    
    /**
     * Lấy lịch sử ôn tập
     * 
     * @param string $userId ID của người dùng
     * @param int $limit Số lượng tối đa
     * @param int $offset Vị trí bắt đầu
     * @return array Lịch sử ôn tập
     */
    public function getReviewHistory($userId, $limit = 20, $offset = 0) {
        // Lấy danh sách phiên ôn tập
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy chi tiết từng phiên
        foreach ($sessions as &$session) {
            // Số lượng từ đúng/sai
            $query = "SELECT 
                      COUNT(CASE WHEN rr.result = 'correct' THEN 1 END) AS correct_count,
                      COUNT(CASE WHEN rr.result = 'incorrect' THEN 1 END) AS incorrect_count
                      FROM session_words sw
                      JOIN review_results rr ON rr.word_id = sw.word_id AND rr.user_id = :user_id
                      WHERE sw.session_id = :session_id
                      AND DATE(rr.created_at) = DATE(:session_date)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":session_id", $session['id']);
            $stmt->bindParam(":session_date", $session['created_at']);
            $stmt->execute();
            
            $results = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $session['correct_count'] = $results['correct_count'] ?? 0;
            $session['incorrect_count'] = $results['incorrect_count'] ?? 0;
            $session['accuracy'] = $session['words_count'] > 0 
                ? round(($session['correct_count'] / $session['words_count']) * 100, 2) 
                : 0;
        }
        
        return $sessions;
    }
    
    /**
     * Lấy danh sách từ cần ôn tập
     * 
     * @param string $userId ID của người dùng
     * @param int $limit Số lượng tối đa
     * @return array Danh sách từ
     */
    public function getWordsForReview($userId, $limit = 20) {
        $today = date('Y-m-d');
        
        $query = "SELECT w.*, un.srs_level, un.next_review_date,
                  s.definition as meaning
                  FROM words w
                  JOIN user_notes un ON w.id = un.word_id
                  JOIN senses s ON w.id = s.word_id AND s.is_primary = 1
                  WHERE un.user_id = :user_id
                  AND (un.next_review_date <= :today OR un.next_review_date IS NULL)
                  AND un.status = 'learning'
                  ORDER BY 
                  CASE 
                    WHEN un.next_review_date IS NULL THEN 0
                    ELSE 1
                  END,
                  un.next_review_date ASC,
                  un.srs_level ASC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":today", $today);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách từ đang chờ ôn tập
     * 
     * @param string $userId ID của người dùng
     * @return array Danh sách từ
     */
    public function getPendingReviews($userId) {
        $today = date('Y-m-d');
        
        $query = "SELECT COUNT(*) AS count FROM user_notes
                  WHERE user_id = :user_id
                  AND next_review_date <= :today
                  AND status = 'learning'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":today", $today);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Tính toán cấp độ SRS mới dựa trên kết quả ôn tập
     * 
     * @param array $note Thông tin note
     * @param string $result Kết quả ôn tập
     * @return int Cấp độ SRS mới
     */
    private function calculateNewSrsLevel($note, $result) {
        $currentLevel = $note['srs_level'] ?? 0;
        
        if ($result === 'correct') {
            // Trả lời đúng, tăng cấp độ
            return min($currentLevel + 1, 9); // Tối đa là cấp 9
        } else {
            // Trả lời sai, giảm cấp độ
            return max($currentLevel - 2, 0); // Tối thiểu là cấp 0
        }
    }
    
    /**
     * Tính toán ngày ôn tập tiếp theo dựa trên cấp độ SRS
     * 
     * @param int $srsLevel Cấp độ SRS
     * @return string Ngày ôn tập tiếp theo
     */
    private function calculateNextReviewDate($srsLevel) {
        $intervals = [
            0 => '+4 hours',
            1 => '+8 hours',
            2 => '+1 day',
            3 => '+3 days',
            4 => '+7 days',
            5 => '+14 days',
            6 => '+30 days',
            7 => '+60 days',
            8 => '+120 days',
            9 => '+240 days'
        ];
        
        $interval = $intervals[$srsLevel] ?? '+1 day';
        return date('Y-m-d H:i:s', strtotime($interval));
    }
    
    /**
     * Hoàn thành phiên ôn tập
     * 
     * @param string $sessionId ID của phiên ôn tập
     * @return boolean Kết quả cập nhật
     */
    public function completeSession($sessionId) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'completed', 
                      completed_at = NOW() 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $sessionId);
        
        return $stmt->execute();
    }
}