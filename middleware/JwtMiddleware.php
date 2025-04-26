<?php
class JwtMiddleware {
    private $secretKey = 'your-secret-key';
    
    public function validateToken() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            exit();
        }
        
        $jwt = $matches[1];
        
        if (!$this->decodeJWT($jwt)) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token"]);
            exit();
        }
        
        return true;
    }
    
    private function decodeJWT($jwt) {
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) != 3) return false;
        
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        $signature = str_replace(['-', '_'], ['+', '/'], $tokenParts[2]);
        
        $headerData = json_decode($header, true);
        $payloadData = json_decode($payload, true);
        
        // Kiểm tra hết hạn
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }
        
        // Kiểm tra chữ ký
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $expectedSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
        
        if ($signature !== $expectedSignatureEncoded) {
            return false;
        }
        
        return $payloadData;
    }
}