<?php
// Set header
header('Content-Type: application/json');

try {
    // Include required files
    require_once 'controllers/LearningPathController.php';
    require_once 'middleware/JwtMiddleware.php';

    // Initialize controller and middleware
    $controller = new LearningPathController();
    $jwtMiddleware = new JwtMiddleware();

    // Apply JWT middleware to all routes
    $jwtMiddleware->handleRequest();

    // Get action from request
    $action = $_GET['action'] ?? '';

    // Handle routes
    switch ($action) {
        case 'get-path':
            $controller->getLearningPath();
            break;
        case 'update-goals':
            $controller->updateLearningGoals();
            break;
        case 'get-goals':
            $controller->getLearningGoals();
            break;
        case 'get-progress':
            $controller->getLearningProgress();
            break;
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Route not found'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}