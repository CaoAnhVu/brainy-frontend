<?php
require_once 'controllers/SocialController.php';

// Khởi tạo controller
$socialController = new SocialController();

// Xử lý các loại request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Sử dụng middleware xác thực JWT cho tất cả các route
$socialController->useJwtMiddleware();

// Xử lý các route
switch ($method) {
    case 'GET':
        switch ($action) {
            case 'friends':
                // GET /api/social.php?action=friends&limit=20&offset=0&query=search_term
                $socialController->getFriends();
                break;
                
            case 'friend-requests':
                // GET /api/social.php?action=friend-requests
                $socialController->getFriendRequests();
                break;
                
            case 'feed':
                // GET /api/social.php?action=feed&limit=20&offset=0
                $socialController->getUserFeed();
                break;
                
            case 'post':
                // GET /api/social.php?action=post&postId=123
                $socialController->getPost();
                break;
                
            case 'comments':
                // GET /api/social.php?action=comments&postId=123&limit=20&offset=0
                $socialController->getComments();
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
            case 'send-friend-request':
                // POST /api/social.php?action=send-friend-request
                // { "friendId": 123 }
                $socialController->sendFriendRequest();
                break;
                
            case 'respond-friend-request':
                // POST /api/social.php?action=respond-friend-request
                // { "requestId": 123, "action": "accept" }
                $socialController->respondToFriendRequest();
                break;
                
            case 'unfriend':
                // POST /api/social.php?action=unfriend
                // { "friendId": 123 }
                $socialController->unfriend();
                break;
                
            case 'create-post':
                // POST /api/social.php?action=create-post
                // { "content": "Hello world", "attachments": [], "privacy": "public" }
                $socialController->createPost();
                break;
                
            case 'delete-post':
                // POST /api/social.php?action=delete-post
                // { "postId": 123 }
                $socialController->deletePost();
                break;
                
            case 'like-post':
                // POST /api/social.php?action=like-post
                // { "postId": 123 }
                $socialController->likePost();
                break;
                
            case 'unlike-post':
                // POST /api/social.php?action=unlike-post
                // { "postId": 123 }
                $socialController->unlikePost();
                break;
                
            case 'add-comment':
                // POST /api/social.php?action=add-comment
                // { "postId": 123, "content": "Nice post!" }
                $socialController->addComment();
                break;
                
            case 'delete-comment':
                // POST /api/social.php?action=delete-comment
                // { "commentId": 123 }
                $socialController->deleteComment();
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