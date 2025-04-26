<?php
require_once 'controllers/AchievementController.php';
require_once 'middleware/JwtMiddleware.php';

$controller = new AchievementController();
$jwtMiddleware = new JwtMiddleware();

// Apply JWT middleware
$jwtMiddleware->handleRequest();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $controller->getUserAchievements();
        break;
    case 'check':
        $controller->checkAchievement();
        break;
    case 'progress':
        $controller->getAchievementProgress();
        break;
    case 'claim-reward':
        $controller->claimAchievementReward();
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Route not found'
        ]);
}