<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Google_Client;

class GoogleAuthMiddleware {
    private $client;

    public function __construct() {
        $this->client = new Google_Client(['client_id' => getenv('GOOGLE_CLIENT_ID')]);
    }

    public function verifyGoogleToken($idToken) {
        try {
            $payload = $this->client->verifyIdToken($idToken);
            if ($payload) {
                return $payload;
            }
            return false;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
} 