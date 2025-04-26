<?php
require_once 'controllers/BaseController.php';
require_once 'services/AchievementService.php';
require_once 'services/UserProgressService.php';

class AchievementController extends BaseController {
    private $achievementService;
    private $userProgressService;

    public function __construct() {
        parent::__construct();
        $this->achievementService = new AchievementService($this->conn);
        $this->userProgressService = new UserProgressService($this->conn);
    }

    public function getUserAchievements() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $achievements = $this->achievementService->getUserAchievements($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $achievements
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error getting achievements: ' . $e->getMessage()
            ]);
        }
    }

    public function checkAchievement() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['type'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Achievement type is required'
                ]);
                return;
            }

            $newAchievements = $this->achievementService->checkAndAwardAchievements(
                $userId, 
                $data['type']
            );
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'newAchievements' => $newAchievements
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error checking achievements: ' . $e->getMessage()
            ]);
        }
    }

    public function getAchievementProgress() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $progress = $this->achievementService->getAchievementProgress($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $progress
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error getting achievement progress: ' . $e->getMessage()
            ]);
        }
    }

    public function claimAchievementReward() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['achievementId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Achievement ID is required'
                ]);
                return;
            }

            $reward = $this->achievementService->claimAchievementReward(
                $userId, 
                $data['achievementId']
            );
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $reward
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error claiming reward: ' . $e->getMessage()
            ]);
        }
    }
}