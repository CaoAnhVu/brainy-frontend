<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/LessonService.php';

class LessonController {
    private $lessonService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->lessonService = new LessonService($db);
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->category_id) || !isset($data->title)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin bắt buộc"]);
            return;
        }
        
        $lessonData = [
            'category_id' => $data->category_id,
            'title' => $data->title,
            'sub_title' => $data->sub_title ?? null,
            'cloudinary_file_id' => $data->cloudinary_file_id ?? null,
            'order_index' => $data->order_index ?? null
        ];
        
        $lessonId = $this->lessonService->createLesson($lessonData);
        
        if ($lessonId) {
            $lesson = $this->lessonService->read($lessonId);
            http_response_code(201);
            echo json_encode([
                "message" => "Tạo bài học thành công",
                "lesson" => $lesson
            ]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể tạo bài học"]);
        }
    }

    public function read() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
        $withWords = isset($_GET['with_words']) && $_GET['with_words'] === 'true';
        
        if ($id) {
            if ($withWords) {
                $lesson = $this->lessonService->getLessonWithWords($id);
            } else {
                $lesson = $this->lessonService->read($id);
            }
            
            if ($lesson) {
                http_response_code(200);
                echo json_encode($lesson);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Không tìm thấy bài học"]);
            }
            return;
        }
        
        if ($categoryId) {
            $lessons = $this->lessonService->getLessonsByCategory($categoryId);
            http_response_code(200);
            echo json_encode($lessons);
        } else {
            $lessons = $this->lessonService->read();
            http_response_code(200);
            echo json_encode($lessons);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID bài học"]);
            return;
        }
        
        $updateData = [];
        if (isset($data->title)) $updateData['title'] = $data->title;
        if (isset($data->sub_title)) $updateData['sub_title'] = $data->sub_title;
        if (isset($data->cloudinary_file_id)) $updateData['cloudinary_file_id'] = $data->cloudinary_file_id;
        if (isset($data->category_id)) $updateData['category_id'] = $data->category_id;
        
        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode(["message" => "Không có dữ liệu để cập nhật"]);
            return;
        }
        
        $result = $this->lessonService->update($data->id, $updateData);
        
        if ($result) {
            $lesson = $this->lessonService->read($data->id);
            http_response_code(200);
            echo json_encode([
                "message" => "Cập nhật bài học thành công",
                "lesson" => $lesson
            ]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể cập nhật bài học"]);
        }
    }
    
    public function updateOrder() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->id) || !isset($data->order_index)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin bắt buộc"]);
            return;
        }
        
        $result = $this->lessonService->updateOrderIndex($data->id, $data->order_index);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Cập nhật thứ tự bài học thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể cập nhật thứ tự bài học"]);
        }
    }

    public function delete() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID bài học"]);
            return;
        }
        
        $result = $this->lessonService->delete($data->id);
        
        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Xóa bài học thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể xóa bài học"]);
        }
    }
} 