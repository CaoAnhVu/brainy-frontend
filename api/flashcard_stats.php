<?php
// flashcard_stats.php
require_once '../services/FlashcardService.php';
require_once '../config/Database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Khởi tạo FlashcardService
$flashcardService = new FlashcardService($db);

// Lấy method từ request
$method = $_SERVER['REQUEST_METHOD'];
$userId = "default-user-id"; // Thay thế bằng id user từ session hoặc auth

// Xử lý request
if ($method === 'GET') {
    $stats = $flashcardService->getFlashcardStats($userId);
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
