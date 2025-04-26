<?php
require_once 'controllers/BaseController.php';
require_once 'services/SocialService.php';
require_once 'services/UserService.php';

class SocialController extends BaseController {
    private $socialService;
    private $userService;

    public function __construct() {
        parent::__construct();
        $this->socialService = new SocialService();
        $this->userService = new UserService();
    }

    public function getFriends() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy thông số từ request
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $query = isset($_GET['query']) ? $_GET['query'] : '';
            
            // Lấy danh sách bạn bè
            $friends = $this->socialService->getFriends($userId, $limit, $offset, $query);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $friends
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving friends: ' . $e->getMessage()
            ]);
        }
    }

    public function getFriendRequests() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy danh sách yêu cầu kết bạn
            $requests = $this->socialService->getFriendRequests($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $requests
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving friend requests: ' . $e->getMessage()
            ]);
        }
    }

    public function sendFriendRequest() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['friendId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Friend ID is required'
                ]);
                return;
            }
            
            $friendId = $data['friendId'];
            
            // Gửi yêu cầu kết bạn
            $result = $this->socialService->sendFriendRequest($userId, $friendId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error sending friend request: ' . $e->getMessage()
            ]);
        }
    }

    public function respondToFriendRequest() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['requestId']) || !isset($data['action'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Request ID and action are required'
                ]);
                return;
            }
            
            $requestId = $data['requestId'];
            $action = $data['action']; // 'accept' or 'reject'
            
            // Phản hồi yêu cầu kết bạn
            $result = $this->socialService->respondToFriendRequest($userId, $requestId, $action);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error responding to friend request: ' . $e->getMessage()
            ]);
        }
    }

    public function unfriend() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['friendId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Friend ID is required'
                ]);
                return;
            }
            
            $friendId = $data['friendId'];
            
            // Hủy kết bạn
            $result = $this->socialService->unfriend($userId, $friendId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error unfriending: ' . $e->getMessage()
            ]);
        }
    }

    public function getUserFeed() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Lấy thông số từ request
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            // Lấy feed người dùng
            $feed = $this->socialService->getUserFeed($userId, $limit, $offset);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $feed
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving user feed: ' . $e->getMessage()
            ]);
        }
    }

    public function createPost() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['content'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post content is required'
                ]);
                return;
            }
            
            $content = $data['content'];
            $attachments = isset($data['attachments']) ? $data['attachments'] : [];
            $privacy = isset($data['privacy']) ? $data['privacy'] : 'public';
            
            // Tạo bài đăng
            $postId = $this->socialService->createPost($userId, $content, $attachments, $privacy);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'postId' => $postId
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error creating post: ' . $e->getMessage()
            ]);
        }
    }

    public function getPost() {
        try {
            $userId = $this->getUserIdFromToken();
            
            if (!isset($_GET['postId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                return;
            }
            
            $postId = $_GET['postId'];
            
            // Lấy thông tin bài đăng
            $post = $this->socialService->getPost($userId, $postId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $post
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving post: ' . $e->getMessage()
            ]);
        }
    }

    public function deletePost() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['postId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                return;
            }
            
            $postId = $data['postId'];
            
            // Xóa bài đăng
            $result = $this->socialService->deletePost($userId, $postId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error deleting post: ' . $e->getMessage()
            ]);
        }
    }

    public function likePost() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['postId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                return;
            }
            
            $postId = $data['postId'];
            
            // Thích bài đăng
            $result = $this->socialService->likePost($userId, $postId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error liking post: ' . $e->getMessage()
            ]);
        }
    }

    public function unlikePost() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['postId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                return;
            }
            
            $postId = $data['postId'];
            
            // Hủy thích bài đăng
            $result = $this->socialService->unlikePost($userId, $postId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error unliking post: ' . $e->getMessage()
            ]);
        }
    }

    public function getComments() {
        try {
            $userId = $this->getUserIdFromToken();
            
            if (!isset($_GET['postId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                return;
            }
            
            $postId = $_GET['postId'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            // Lấy bình luận
            $comments = $this->socialService->getComments($postId, $limit, $offset);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $comments
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error retrieving comments: ' . $e->getMessage()
            ]);
        }
    }

    public function addComment() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['postId']) || !isset($data['content'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Post ID and comment content are required'
                ]);
                return;
            }
            
            $postId = $data['postId'];
            $content = $data['content'];
            
            // Thêm bình luận
            $commentId = $this->socialService->addComment($userId, $postId, $content);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => [
                    'commentId' => $commentId
                ]
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error adding comment: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteComment() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate request data
            if (!isset($data['commentId'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Comment ID is required'
                ]);
                return;
            }
            
            $commentId = $data['commentId'];
            
            // Xóa bình luận
            $result = $this->socialService->deleteComment($userId, $commentId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error deleting comment: ' . $e->getMessage()
            ]);
        }
    }
} 