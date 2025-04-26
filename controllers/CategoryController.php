<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/CategoryService.php';

class CategoryController {
    private $categoryService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->categoryService = new CategoryService($db);
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['title']) || !isset($data['total'])) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin danh mục"]);
            return;
        }

        $result = $this->categoryService->createCategory($data);

        if ($result) {
            http_response_code(201);
            echo json_encode(["message" => "Tạo danh mục thành công", "id" => $result]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể tạo danh mục"]);
        }
    }

    public function read() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if ($id) {
            // Nếu có tham số lessons=true, trả về danh mục với các bài học
            if (isset($_GET['lessons']) && $_GET['lessons'] === 'true') {
                $result = $this->categoryService->getCategoryWithLessons($id);
            } else {
                $result = $this->categoryService->getOne($id);
            }
            
            if ($result) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Không tìm thấy danh mục"]);
            }
        } else {
            // Trả về tất cả danh mục
            $result = $this->categoryService->getAll();
            http_response_code(200);
            echo json_encode($result);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID danh mục"]);
            return;
        }

        $id = $data['id'];
        unset($data['id']); // Xóa ID khỏi dữ liệu cập nhật

        $result = $this->categoryService->updateCategory($id, $data);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Cập nhật danh mục thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể cập nhật danh mục"]);
        }
    }

    public function delete() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID danh mục"]);
            return;
        }

        $result = $this->categoryService->deleteCategory($data['id']);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Xóa danh mục thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể xóa danh mục"]);
        }
    }
} 