<?php
class SearchService {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function search($keyword) {
        $keyword = "%$keyword%";
        
        // Tìm kiếm từ vựng
        $query = "
            SELECT 
                'word' as type,
                id,
                word as title,
                pos as subtitle,
                NULL as description
            FROM 
                words 
            WHERE 
                word LIKE :keyword OR
                phonetic LIKE :keyword OR
                phonetic_text LIKE :keyword
            UNION
            
            -- Tìm kiếm danh mục
            SELECT 
                'category' as type,
                id,
                title,
                NULL as subtitle,
                description
            FROM 
                categories 
            WHERE 
                title LIKE :keyword OR
                description LIKE :keyword
            UNION
            
            -- Tìm kiếm bài học
            SELECT 
                'lesson' as type,
                id,
                title,
                sub_title as subtitle,
                NULL as description
            FROM 
                lessons 
            WHERE 
                title LIKE :keyword OR
                sub_title LIKE :keyword
            LIMIT 50";
            
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":keyword", $keyword);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}