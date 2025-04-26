<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/WordService.php';

class WordController {
    private $wordService;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->wordService = new WordService($db);
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['word']) || !isset($data['senses']) || empty($data['senses'])) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin bắt buộc"]);
            return;
        }

        try {
            $wordData = [
                'word' => $data['word'],
                'lesson_id' => $data['lesson_id'] ?? null,
                'pos' => $data['pos'] ?? null,
                'phonetic' => $data['phonetic'] ?? null,
                'phonetic_text' => $data['phonetic_text'] ?? null,
                'audio_id' => $data['audio_id'] ?? null,
                'image_id' => $data['image_id'] ?? null
            ];

            $result = $this->wordService->createWordWithSenses($wordData, $data['senses']);

            http_response_code(201);
            echo json_encode([
                "message" => "Tạo từ mới thành công",
                "word" => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Lỗi khi tạo từ mới",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function getByLesson() {
        if (!isset($_GET['lesson_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu lesson_id"]);
            return;
        }

        try {
            $words = $this->wordService->getWordsByLesson($_GET['lesson_id']);
            http_response_code(200);
            echo json_encode($words);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Lỗi khi lấy danh sách từ",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function search() {
        if (!isset($_GET['keyword'])) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu từ khóa tìm kiếm"]);
            return;
        }

        try {
            $words = $this->wordService->searchWords($_GET['keyword']);
            http_response_code(200);
            echo json_encode($words);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Lỗi khi tìm kiếm từ",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function read() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu ID từ"]);
            return;
        }

        try {
            $word = $this->wordService->getWordWithSensesAndExamples($id);
            
            if ($word) {
                http_response_code(200);
                echo json_encode($word);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Không tìm thấy từ"]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Lỗi khi lấy thông tin từ",
                "error" => $e->getMessage()
            ]);
        }
    }
} 