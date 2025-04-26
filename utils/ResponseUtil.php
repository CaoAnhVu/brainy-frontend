<?php
class ResponseUtil {
    public static function success($data) {
        return json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public static function error($message) {
        return json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }

    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 401);
    }

    public static function forbidden($message = 'Forbidden') {
        return self::error($message, 403);
    }

    public static function notFound($message = 'Not found') {
        return self::error($message, 404);
    }

    public static function validation($errors) {
        return self::error([
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
}
