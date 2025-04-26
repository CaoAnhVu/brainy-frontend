<?php
require_once 'controllers/NotificationController.php';

// Khởi tạo controller
$notificationController = new NotificationController();

// Xử lý các loại request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Sử dụng middleware xác thực JWT cho tất cả các route
$notificationController->useJwtMiddleware();

// Xử lý các route
switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                // GET /api/notifications.php?action=list&limit=20&offset=0
                $notificationController->getUserNotifications();
                break;
                
            case 'unread-count':
                // GET /api/notifications.php?action=unread-count
                $notificationController->getUnreadCount();
                break;
                
            case 'settings':
                // GET /api/notifications.php?action=settings
                $notificationController->getNotificationSettings();
                break;
                
            default:
                // Default: lấy danh sách thông báo
                $notificationController->getUserNotifications();
                break;
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'mark-as-read':
                // POST /api/notifications.php?action=mark-as-read
                // { "notificationId": "abc123" }
                $notificationController->markAsRead();
                break;
                
            case 'mark-all-read':
                // POST /api/notifications.php?action=mark-all-read
                $notificationController->markAllAsRead();
                break;
                
            case 'update-settings':
                // POST /api/notifications.php?action=update-settings
                // { "settings": { "email_notifications": true, ... } }
                $notificationController->updateNotificationSettings();
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