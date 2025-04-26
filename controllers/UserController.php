<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/UserService.php';

class UserController {
    private $userService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->userService = new UserService($db);
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin"]);
            return;
        }

        $result = $this->userService->createUser([
            'username' => $data->username,
            'email' => $data->email,
            'password' => $data->password,
            'full_name' => $data->full_name ?? null,
            'avatar_url' => $data->avatar_url ?? null
        ]);

        if ($result) {
            http_response_code(201);
            echo json_encode(["message" => "Tạo người dùng thành công", "id" => $result]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể tạo người dùng"]);
        }
    }

    public function read() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $result = $this->userService->read($id);

        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Không tìm thấy người dùng"]);
        }
    }

    public function update() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID người dùng"]);
            return;
        }

        $updateData = [];
        if (isset($data->username)) $updateData['username'] = $data->username;
        if (isset($data->email)) $updateData['email'] = $data->email;
        if (isset($data->full_name)) $updateData['full_name'] = $data->full_name;
        if (isset($data->avatar_url)) $updateData['avatar_url'] = $data->avatar_url;
        if (isset($data->status)) $updateData['status'] = $data->status;

        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode(["message" => "Không có dữ liệu để cập nhật"]);
            return;
        }

        $result = $this->userService->update($data->id, $updateData);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Cập nhật thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể cập nhật người dùng"]);
        }
    }

    public function delete() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->id)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID người dùng"]);
            return;
        }

        $result = $this->userService->delete($data->id);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Xóa người dùng thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Không thể xóa người dùng"]);
        }
    }
} 