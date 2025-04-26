<?php
require_once __DIR__ . '/BaseService.php';

class LessonService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "lessons";
    }
    
    public function getLessonsByCategory($categoryId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE category_id = :category_id ORDER BY order_index ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":category_id", $categoryId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createLesson($data) {
        if (!isset($data['order_index'])) {
            // Nếu không cung cấp order_index, tự động tính toán theo số bài học hiện có
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE category_id = :category_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":category_id", $data['category_id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $data['order_index'] = $row['count'] + 1;
        }
        
        return $this->create($data);
    }
    
    public function updateOrderIndex($lessonId, $newIndex) {
        $lesson = $this->read($lessonId);
        if (!$lesson) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " SET order_index = :new_index WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":new_index", $newIndex);
        $stmt->bindParam(":id", $lessonId);
        
        return $stmt->execute();
    }
    
    public function getLessonWithWords($lessonId) {
        $lesson = $this->read($lessonId);
        
        if (!$lesson) {
            return false;
        }
        
        // Lấy danh sách các từ trong bài học
        $query = "SELECT * FROM words WHERE lesson_id = :lesson_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":lesson_id", $lessonId);
        $stmt->execute();
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Thêm danh sách từ vào thông tin bài học
        $lesson['words'] = $words;
        
        return $lesson;
    }
    
    public function getWordCount($lessonId) {
        $query = "SELECT COUNT(*) as count FROM words WHERE lesson_id = :lesson_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":lesson_id", $lessonId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'];
    }
} 