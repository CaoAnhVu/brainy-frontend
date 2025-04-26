<?php
require_once '../config/Database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy method từ request
$method = $_SERVER['REQUEST_METHOD'];

// Xử lý request
switch ($method) {
    case 'GET':
        // Lấy danh sách chủ đề từ database
        $topics = getTopics($db);
        echo json_encode([
            'success' => true,
            'data' => $topics
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
}

// Function to get all topics
function getTopics($db) {
    // Group words by topics C1 & C2
    $sql = "SELECT 
                CONCAT(level, ' - ', topic) as name,
                COUNT(*) as word_count
            FROM (
                SELECT DISTINCT word_id, 
                       CASE 
                           WHEN topic LIKE 'C1%' THEN 'C1' 
                           WHEN topic LIKE 'C2%' THEN 'C2'
                           WHEN topic LIKE 'B1%' THEN 'B1'
                           WHEN topic LIKE 'B2%' THEN 'B2'
                           WHEN topic LIKE 'A1%' THEN 'A1'
                           WHEN topic LIKE 'A2%' THEN 'A2'
                       END as level,
                       SUBSTRING_INDEX(topic, ' ', -2) as topic
                FROM topic_words
            ) as t
            GROUP BY level, topic
            ORDER BY level, topic";
            
    $result = $db->query($sql);
    
    if (!$result) {
        return [];
    }
    
    $topics = [];
    while ($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }
    
    return $topics;
}