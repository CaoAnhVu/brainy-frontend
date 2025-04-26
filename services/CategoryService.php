<?php
require_once __DIR__ . '/BaseService.php';

class CategoryService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "categories";
    }

    public function getAll() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY title ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAll: " . $e->getMessage());
            return [];
        }
    }

    public function getOne($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getOne: " . $e->getMessage());
            return false;
        }
    }

    public function createCategory($data) {
        // Đảm bảo các trường bắt buộc
        if (!isset($data['title']) || !isset($data['total'])) {
            return false;
        }

        // Đặt giá trị mặc định nếu cần
        if (!isset($data['progress'])) {
            $data['progress'] = 0;
        }

        return $this->create($data);
    }

    public function updateCategory($id, $data) {
        return $this->update($id, $data);
    }

    public function deleteCategory($id) {
        return $this->delete($id);
    }

    public function getCategoryWithLessons($id) {
        $category = $this->getOne($id);
        
        if (!$category) {
            return false;
        }

        try {
            $query = "SELECT * FROM lessons WHERE category_id = :category_id ORDER BY order_index ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":category_id", $id);
            $stmt->execute();
            $category['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $category;
        } catch (PDOException $e) {
            error_log("Error in getCategoryWithLessons: " . $e->getMessage());
            $category['lessons'] = [];
            return $category;
        }
    }
} 