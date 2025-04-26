<?php
// quiz.php
require_once '../services/WordService.php';
require_once '../config/Database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Khởi tạo WordService
$wordService = new WordService($db);

// Lấy method từ request
$method = $_SERVER['REQUEST_METHOD'];

// Xử lý request
switch ($method) {
    case 'GET':
        // Lấy topic từ query parameters
        $topic = isset($_GET['topic']) ? $_GET['topic'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        if (!$topic) {
            echo json_encode([
                'success' => false,
                'message' => 'Topic parameter is required'
            ]);
            break;
        }
        
        // Lấy từ vựng theo topic
        $words = getWordsByTopic($db, $topic, $limit);
        
        // Tạo câu hỏi quiz từ từ vựng
        $questions = createQuizQuestions($words);
        
        echo json_encode([
            'success' => true,
            'data' => $questions
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
}

// Function to get words by topic
function getWordsByTopic($db, $topic, $limit) {
    $sql = "SELECT w.*, s.definition, s.example
            FROM words w
            JOIN senses s ON w.id = s.word_id
            JOIN topic_words tw ON w.id = tw.word_id
            WHERE tw.topic LIKE ?
            ORDER BY RAND()
            LIMIT ?";
            
    $stmt = $db->prepare($sql);
    $topicParam = "%$topic%";
    $stmt->bind_param("si", $topicParam, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $words = [];
    while ($row = $result->fetch_assoc()) {
        $words[] = $row;
    }
    
    return $words;
}

// Function to create quiz questions from words
function createQuizQuestions($words) {
    $questions = [];
    
    foreach ($words as $word) {
        // Create main question with definition
        $mainQuestion = [
            'question' => "What is the meaning of '" . $word['word'] . "'?",
            'options' => [],
            'correctAnswer' => $word['definition']
        ];
        
        // Add correct answer
        $mainQuestion['options'][] = $word['definition'];
        
        // Add 3 wrong answers from other words
        $wrongAnswers = getWrongAnswers($words, $word['id'], 3);
        $mainQuestion['options'] = array_merge($mainQuestion['options'], $wrongAnswers);
        
        // Shuffle options
        shuffle($mainQuestion['options']);
        
        $questions[] = $mainQuestion;
    }
    
    return $questions;
}

// Function to get wrong answers from other words
function getWrongAnswers($words, $currentWordId, $count) {
    $wrongAnswers = [];
    $otherWords = array_filter($words, function($word) use ($currentWordId) {
        return $word['id'] !== $currentWordId;
    });
    
    // Shuffle other words to get random wrong answers
    shuffle($otherWords);
    
    // Get up to $count wrong answers
    for ($i = 0; $i < min($count, count($otherWords)); $i++) {
        $wrongAnswers[] = $otherWords[$i]['definition'];
    }
    
    return $wrongAnswers;
}