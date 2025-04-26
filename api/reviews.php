<?php
require_once 'controllers/ReviewController.php';

// Khởi tạo controller
$reviewController = new ReviewController();

// Xử lý các loại request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Sử dụng middleware xác thực JWT cho tất cả các route
$reviewController->useJwtMiddleware();

// Xử lý các route
switch ($method) {
    case 'GET':
        switch ($action) {
            case 'words-for-review':
                // GET /api/reviews.php?action=words-for-review&limit=20
                $reviewController->getWordsForReview();
                break;
                
            case 'history':
                // GET /api/reviews.php?action=history&limit=20&offset=0
                $reviewController->getReviewHistory();
                break;
                
            case 'pending':
                // GET /api/reviews.php?action=pending
                $reviewController->getPendingReviews();
                break;
                
            case 'note':
                // GET /api/reviews.php?action=note&wordId=123
                $reviewController->getUserNote();
                break;
                
            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Route not found'
                ]);
                break;
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'start-session':
                // POST /api/reviews.php?action=start-session
                // { "wordIds": [1, 2, 3] }
                $reviewController->startReviewSession();
                break;
                
            case 'submit-result':
                // POST /api/reviews.php?action=submit-result
                // { "sessionId": "abc123", "wordId": 1, "result": "correct", "answer": "example" }
                $reviewController->submitReviewResult();
                break;
                
            case 'complete-session':
                // POST /api/reviews.php?action=complete-session
                // { "sessionId": "abc123" }
                $reviewController->completeReviewSession();
                break;
                
            case 'save-note':
                // POST /api/reviews.php?action=save-note
                // { "wordId": 1, "note": "My note content" }
                $reviewController->saveUserNote();
                break;
                
            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Route not found'
                ]);
                break;
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
} 