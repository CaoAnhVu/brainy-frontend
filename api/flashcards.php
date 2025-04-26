<?php
// flashcards.php
require_once '../services/FlashcardService.php';
require_once '../config/Database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Khởi tạo FlashcardService
$flashcardService = new FlashcardService($db);

// Lấy method và dữ liệu từ request
$method = $_SERVER['REQUEST_METHOD'];
$userId = "default-user-id"; // Thay thế bằng id user từ session hoặc auth

// Xử lý request
switch ($method) {
    case 'GET':
        // Lấy flashcard đến hạn ôn tập
        $flashcards = $flashcardService->getDueFlashcards($userId);
        echo json_encode([
            'success' => true,
            'data' => $flashcards
        ]);
        break;
        
    case 'POST':
        // Tạo flashcard mới
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['word_id'])) {
            // Tạo flashcard đơn lẻ
            $flashcardId = $flashcardService->createFlashcardForUser($userId, $data['word_id']);
            echo json_encode([
                'success' => true,
                'message' => 'Flashcard created successfully',
                'id' => $flashcardId
            ]);
        }
        else if (isset($data['topic'])) {
            // Tạo flashcard cho cả topic
            $count = $flashcardService->createFlashcardsForTopic($userId, $data['topic']);
            echo json_encode([
                'success' => true,
                'message' => "Created $count flashcards for topic {$data['topic']}"
            ]);
        }
        else {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
        }
        break;
        
    case 'PUT':
        // Cập nhật flashcard sau khi ôn tập
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['flashcard_id']) && isset($data['quality'])) {
            $result = $flashcardService->updateFlashcardAfterReview($data['flashcard_id'], $data['quality']);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Flashcard updated successfully' : 'Failed to update flashcard'
            ]);
        }
        else {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
}
