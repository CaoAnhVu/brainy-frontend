<?php
require_once __DIR__ . '/BaseService.php';

class WordService extends BaseService {
    private $sensesTable = "senses";
    private $examplesTable = "examples";

    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "words";
    }

    public function createWordWithSenses($wordData, $senses) {
        try {
            error_log("=== Start creating word ===");
            error_log("Word data: " . json_encode($wordData));
            error_log("Senses data: " . json_encode($senses));

            $this->conn->beginTransaction();

            // 1. Tạo từ mới
            $wordId = $this->create([
                'word' => $wordData['word'],
                'lesson_id' => $wordData['lesson_id'] ?? null,
                'pos' => $wordData['pos'] ?? null,
                'phonetic' => $wordData['phonetic'] ?? null,
                'phonetic_text' => $wordData['phonetic_text'] ?? null,
                'phonetic_am' => $wordData['phonetic_am'] ?? null,
                'phonetic_am_text' => $wordData['phonetic_am_text'] ?? null,
                'audio_id' => $wordData['audio_id'] ?? null,
                'image_id' => $wordData['image_id'] ?? null
            ]);

            error_log("Created word with ID: " . $wordId);

            if (!$wordId) {
                throw new Exception("Không thể tạo từ mới");
            }

            // 2. Thêm từng nghĩa
            foreach ($senses as $sense) {
                // Thêm word_id vào sense data
                $sense['word_id'] = $wordId;
                
                // Đảm bảo có definition
                if (!isset($sense['definition']) || empty($sense['definition'])) {
                    throw new Exception("Definition không được để trống");
                }

                // Tạo sense
                $senseId = $this->createSense($sense);

                if (!$senseId) {
                    throw new Exception("Không thể tạo nghĩa của từ");
                }

                // 3. Thêm các ví dụ cho nghĩa này
                if (isset($sense['examples']) && is_array($sense['examples'])) {
                    foreach ($sense['examples'] as $example) {
                        $exampleResult = $this->createExample([
                            'sense_id' => $senseId,
                            'x' => $example['text'],
                            'cf' => $example['cf'] ?? null
                        ]);
                        
                        if (!$exampleResult) {
                            throw new Exception("Không thể tạo ví dụ");
                        }
                    }
                }
            }

            $this->conn->commit();
            return $this->getWordWithSensesAndExamples($wordId);

        } catch (Exception $e) {
            error_log("Error in createWordWithSenses: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getWordsByLesson($lessonId) {
        $query = "SELECT w.*, 
                        cf.file_url as audio_url,
                        cf2.file_url as image_url
                 FROM " . $this->table_name . " w
                 LEFT JOIN cloudinary_files cf ON w.audio_id = cf.id
                 LEFT JOIN cloudinary_files cf2 ON w.image_id = cf2.id
                 WHERE w.lesson_id = :lesson_id
                 ORDER BY w.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":lesson_id", $lessonId);
        $stmt->execute();

        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy thêm nghĩa và ví dụ cho mỗi từ
        foreach ($words as &$word) {
            $word['senses'] = $this->getWordSenses($word['id']);
        }

        return $words;
    }

    public function searchWords($keyword) {
        $query = "SELECT * FROM " . $this->table_name . "
                 WHERE word LIKE :keyword
                 OR phonetic LIKE :keyword
                 OR phonetic_text LIKE :keyword
                 LIMIT 20";

        $keyword = "%{$keyword}%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":keyword", $keyword);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createSense($senseData) {
        try {
            error_log("=== Start creating sense ===");
            error_log("Sense data: " . json_encode($senseData));

            // Tạo UUID cho sense
            $senseData['id'] = $this->generateUUID();
            error_log("Generated UUID for sense: " . $senseData['id']);

            // Kiểm tra các trường bắt buộc
            if (!isset($senseData['word_id']) || empty($senseData['word_id'])) {
                throw new Exception("word_id là bắt buộc cho sense");
            }
            if (!isset($senseData['definition']) || empty($senseData['definition'])) {
                throw new Exception("definition là bắt buộc cho sense");
            }

            // Tạo câu query
            $fields = array_keys($senseData);
            $placeholders = array_map(function($field) { return ":$field"; }, $fields);
            
            $query = "INSERT INTO " . $this->sensesTable . " 
                     (" . implode(", ", $fields) . ") 
                     VALUES (" . implode(", ", $placeholders) . ")";
            
            error_log("SQL Query: " . $query);
            error_log("Field values: " . json_encode($senseData));

            $stmt = $this->conn->prepare($query);

            // Bind các giá trị
            foreach ($senseData as $key => $value) {
                $stmt->bindValue(":$key", $value);
                error_log("Binding $key = $value");
            }

            // Thực thi query
            $result = $stmt->execute();
            error_log("Execute result: " . ($result ? "true" : "false"));

            if (!$result) {
                error_log("SQL Error: " . json_encode($stmt->errorInfo()));
                return false;
            }

            error_log("=== Sense created successfully ===");
            return $senseData['id'];

        } catch (Exception $e) {
            error_log("Error in createSense: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    private function createExample($exampleData) {
        $fields = [];
        $values = [];
        $params = [];

        foreach ($exampleData as $key => $value) {
            $fields[] = $key;
            $params[] = ":" . $key;
        }

        $query = "INSERT INTO " . $this->examplesTable . " 
                 (" . implode(", ", $fields) . ") 
                 VALUES (" . implode(", ", $params) . ")";

        $stmt = $this->conn->prepare($query);

        foreach ($exampleData as $key => $value) {
            $stmt->bindValue(":" . $key, $value);
        }

        return $stmt->execute();
    }

    private function getWordSenses($wordId) {
        $sql = "SELECT id, word_id, definition 
                FROM senses 
                WHERE word_id = :word_id";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':word_id', $wordId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWordWithSensesAndExamples($wordId) {
        $word = $this->read($wordId);
        if ($word) {
            $word['senses'] = $this->getWordSenses($wordId);
        }
        return $word;
    }
}