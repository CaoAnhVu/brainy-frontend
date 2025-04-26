<?php
require_once 'controllers/BaseController.php';
require_once 'services/ReviewService.php';
require_once 'services/WordService.php';
require_once 'services/UserProgressService.php';

class ReviewController extends BaseController {
    private $reviewService;
    private $wordService;
    private $userProgressService;

    public function __construct() {
        parent::__construct();
        $this->reviewService = new ReviewService();
        $this->wordService = new WordService();
        $this->userProgressService = new UserProgressService();
    }

    public function getWordsForReview() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy thông số từ request
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            
            // Lấy danh sách từ cần ôn tập
            $reviewWords = $this->reviewService->getWordsForReview($userId, $limit);
            
            if (empty($reviewWords)) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'No words to review at this time',
                    'data' => []
                ]);
                return;
            }
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $reviewWords
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving review words: ' . $e->getMessage()
            ]);
        }
    }

    public function startReviewSession() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['wordIds']) || !is_array($data['wordIds'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Word IDs are required'
                ]);
                return;
            }
            
            $sessionId = $this->reviewService->createReviewSession($userId, $data['wordIds']);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'sessionId' => $sessionId
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error starting review session: ' . $e->getMessage()
            ]);
        }
    }

    public function submitReviewResult() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['sessionId']) || !isset($data['wordId']) || !isset($data['result'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Session ID, word ID, and result are required'
                ]);
                return;
            }
            
            $sessionId = $data['sessionId'];
            $wordId = $data['wordId'];
            $result = $data['result']; // 'correct', 'incorrect', or 'hard'
            $answer = isset($data['answer']) ? $data['answer'] : null;
            
            // Process review result
            $reviewResult = $this->reviewService->processReviewResult(
                $userId, 
                $sessionId, 
                $wordId, 
                $result,
                $answer
            );
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $reviewResult
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error submitting review result: ' . $e->getMessage()
            ]);
        }
    }

    public function completeReviewSession() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['sessionId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Session ID is required'
                ]);
                return;
            }
            
            $sessionId = $data['sessionId'];
            
            // Đánh dấu hoàn thành phiên ôn tập
            $summary = $this->reviewService->completeSession($sessionId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $summary
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error completing review session: ' . $e->getMessage()
            ]);
        }
    }

    public function getReviewHistory() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy thông số từ request
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            // Lấy lịch sử ôn tập
            $history = $this->reviewService->getReviewHistory($userId, $limit, $offset);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $history
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving review history: ' . $e->getMessage()
            ]);
        }
    }

    public function getPendingReviews() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy số lượng từ đang chờ ôn tập
            $count = $this->reviewService->getPendingReviews($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'pendingCount' => $count
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving pending reviews: ' . $e->getMessage()
            ]);
        }
    }

    public function getUserNote() {
        try {
            $userId = $this->getUserIdFromToken();
            
            if (!isset($_GET['wordId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Word ID is required'
                ]);
                return;
            }
            
            $wordId = (int)$_GET['wordId'];
            
            // Lấy ghi chú của người dùng
            $note = $this->reviewService->getUserNote($userId, $wordId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $note
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving user note: ' . $e->getMessage()
            ]);
        }
    }

    public function saveUserNote() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['wordId']) || !isset($data['note'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Word ID and note content are required'
                ]);
                return;
            }
            
            $wordId = $data['wordId'];
            $note = $data['note'];
            
            // Lưu ghi chú
            $noteId = $this->reviewService->saveUserNote($userId, $wordId, $note);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'noteId' => $noteId
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error saving user note: ' . $e->getMessage()
            ]);
        }
    }
} 