<?php
class ValidationUtil {
    public static function validateTwoFactorCode($code) {
        // Kiểm tra mã 2FA (6 chữ số)
        return preg_match('/^[0-9]{6}$/', $code);
    }

    public static function validateBackupCode($code) {
        // Kiểm tra backup code (10 ký tự alphanumeric)
        return preg_match('/^[A-Za-z0-9]{10}$/', $code);
    }

    public static function validateUserId($userId) {
        // Kiểm tra UUID format
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $userId);
    }

    public static function validateSecretKey($secretKey) {
        // Kiểm tra secret key format (base32 string)
        return preg_match('/^[A-Z2-7]{32}$/', $secretKey);
    }

    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)));
    }
}