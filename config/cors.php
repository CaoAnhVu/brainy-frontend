<?php
// Cấu hình CORS cho API
class Cors {
    // Danh sách domain được phép truy cập API
    private $allowedOrigins = [
        'http://localhost:5173',    // Vite dev server
        'http://localhost:3000',    // Dev server
        'https://brainy-d0c87.firebaseapp.com/' // Production server
    ];
    
    // HTTP methods được cho phép
    private $allowedMethods = [
        'GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'
    ];
    
    // Headers được cho phép
    private $allowedHeaders = [
        'Content-Type', 
        'Authorization', 
        'X-Requested-With',
        'Accept',
        'Origin'
    ];
    
    public function __construct() {
        $this->handle();
    }
    
    public function handle() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Kiểm tra origin có được phép hay không
        // Nếu $allowedOrigins = ['*'] thì cho phép tất cả
        if (in_array('*', $this->allowedOrigins) || in_array($origin, $this->allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        // Các header CORS khác
        header("Access-Control-Allow-Methods: " . implode(', ', $this->allowedMethods));
        header("Access-Control-Allow-Headers: " . implode(', ', $this->allowedHeaders));
        header("Access-Control-Max-Age: 3600"); // 1 hour
        
        // Xử lý preflight request
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// Tự động xử lý CORS khi file được include
$cors = new Cors();