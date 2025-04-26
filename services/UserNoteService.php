<?php
require_once __DIR__ . '/BaseService.php';

class UserNoteService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "user_notes";
    }
    
    public function getNotesByUser($userId) {
        $query = "
            SELECT un.*, w.word
            FROM " . $this->table_name . " un
            JOIN words w ON un.word_id = w.id
            WHERE un.user_id = :user_id
            ORDER BY un.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getNotesByWord($userId, $wordId) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND word_id = :word_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":word_id", $wordId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addNote($userId, $wordId, $note) {
        // Thêm ghi chú cho từ
    }
    
    public function getUserNotes($userId, $wordId = null) {
        // Lấy danh sách ghi chú
    }
}