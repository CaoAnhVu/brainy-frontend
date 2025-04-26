<?php
class JwtUtil {
    private $secretKey;
    private $algorithm;
    
    public function __construct() {
        $this->secretKey = $_ENV['JWT_SECRET'];
        $this->algorithm = 'HS256';
    }

    public function generateToken($payload) {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            $this->secretKey, 
            true
        );
        $base64UrlSignature = $this->base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }

        $header = $this->base64UrlDecode($parts[0]);
        $payload = $this->base64UrlDecode($parts[1]);
        $signature = $this->base64UrlDecode($parts[2]);

        $verifySignature = hash_hmac('sha256', 
            $parts[0] . "." . $parts[1], 
            $this->secretKey, 
            true
        );

        return hash_equals($signature, $verifySignature);
    }

    public function getPayload($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }
        return json_decode($this->base64UrlDecode($parts[1]), true);
    }

    private function base64UrlEncode($data) {
        $base64 = base64_encode($data);
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }

    private function base64UrlDecode($data) {
        $base64 = str_replace(['-', '_'], ['+', '/'], $data);
        return base64_decode($base64);
    }
}
