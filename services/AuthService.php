<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/UserService.php';
require_once __DIR__ . '/SessionService.php';
require_once __DIR__ . '/DeviceService.php';
require_once __DIR__ . '/TwoFactorAuthService.php';
require_once __DIR__ . '/SocialService.php';

class AuthService extends BaseService {
    private $userService;
    private $sessionService;
    private $deviceService;
    private $twoFactorService;
    private $socialService;
    private $refreshTokenTable = "refresh_tokens";
    private $passwordResetTable = "password_resets";

    public function __construct($db) {
        parent::__construct($db);
        $this->userService = new UserService($db);
        $this->sessionService = new SessionService($db);
        $this->deviceService = new DeviceService($db);
        $this->twoFactorService = new TwoFactorAuthService($db);
        $this->socialService = new SocialService($db);
    }

    public function login($email, $password, $deviceInfo = null, $ipAddress = null, $userAgent = null) {
        $user = $this->userService->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->logAuthEvent($user['id'] ?? null, 'login_failed', $ipAddress, $userAgent, 'failed', ['reason' => 'invalid_credentials']);
            return false;
        }

        if (isset($deviceInfo['twoFactorCode'])) {
            if (!$this->twoFactorService->verifyTwoFactor($user['id'], $deviceInfo['twoFactorCode'])) {
                $this->logAuthEvent($user['id'], 'login_2fa_failed', $ipAddress, $userAgent, 'failed', ['reason' => 'invalid_2fa_code']);
                return ['requires_2fa' => true];
            }
        }

        if ($deviceInfo) {
            $this->deviceService->registerDevice($user['id'], $deviceInfo);
        }

        $token = $this->generateJWT($user);
        $this->sessionService->createSession($user['id'], $token, $ipAddress, $userAgent);

        $this->logAuthEvent($user['id'], 'login_success', $ipAddress, $userAgent, 'success');

        return [
            'user' => $user,
            'token' => $token,
            'refresh_token' => $this->generateRefreshToken($user['id'])
        ];
    }

    public function register($data) {
        try {
            error_log("Register data: " . print_r($data, true));
            
            if (is_object($data)) {
                $data = [
                    'id' => $this->generateUUID(),
                    'username' => strtolower(str_replace(' ', '', $data->name)),
                    'email' => $data->email,
                    'password' => password_hash($data->password, PASSWORD_DEFAULT),
                    'full_name' => $data->name,
                    'status' => 'active',
                    'role' => 'user',
                    'email_verified' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            
            error_log("Processed data: " . print_r($data, true));

            if ($this->userService->findByEmail($data['email'])) {
                error_log("Email already exists: " . $data['email']);
                return ResponseUtil::error('Email đã tồn tại');
            }

            $userId = $this->userService->createUser($data);
            error_log("Created user ID: " . $userId);
            
            if (!$userId) {
                error_log("Failed to create user");
                return ResponseUtil::error('Không thể tạo tài khoản');
            }

            $user = $this->userService->read($userId);
            error_log("Read user data: " . print_r($user, true));
            
            return ResponseUtil::success([
                'message' => 'Đăng ký thành công',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name']
                ]
            ]);
        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return ResponseUtil::error('Lỗi: ' . $e->getMessage());
        }
    }

    public function refreshToken($refreshToken) {
        $query = "SELECT * FROM " . $this->refreshTokenTable . " WHERE token = :token AND expires_at > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $refreshToken);
        $stmt->execute();
        
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tokenData) {
            return false;
        }

        $user = $this->userService->read($tokenData['user_id']);
        return [
            'token' => $this->generateJWT($user),
            'refresh_token' => $this->generateRefreshToken($user['id'])
        ];
    }

    public function forgotPassword($email) {
        $user = $this->userService->findByEmail($email);
        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "INSERT INTO " . $this->passwordResetTable . " (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user['id']);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expiresAt);

        return $stmt->execute() ? $token : false;
    }

    public function resetPassword($token, $newPassword) {
        $query = "SELECT * FROM " . $this->passwordResetTable . " WHERE token = :token AND expires_at > NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resetData) {
            return false;
        }

        if ($this->userService->updatePassword($resetData['user_id'], $newPassword)) {
            $query = "DELETE FROM " . $this->passwordResetTable . " WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
            return true;
        }

        return false;
    }

    public function googleAuth($data) {
        try {
            $existingUser = $this->userService->findByEmail($data['email']);
            
            if ($existingUser) {
                if (!$existingUser['google_id']) {
                    $this->userService->update($existingUser['id'], [
                        'google_id' => $data['googleId'],
                        'avatar' => $data['avatar']
                    ]);
                    $existingUser = $this->userService->read($existingUser['id']);
                }
                
                return [
                    'user' => $existingUser,
                    'token' => $this->generateJWT($existingUser),
                    'refresh_token' => $this->generateRefreshToken($existingUser['id'])
                ];
            }

            $userId = $this->userService->createUser([
                'email' => $data['email'],
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'google_id' => $data['googleId'],
                'avatar' => $data['avatar'],
                'password' => bin2hex(random_bytes(16)),
                'email_verified' => true
            ]);

            if (!$userId) {
                return false;
            }

            $newUser = $this->userService->read($userId);
            return [
                'user' => $newUser,
                'token' => $this->generateJWT($newUser),
                'refresh_token' => $this->generateRefreshToken($userId)
            ];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    private function generateJWT($user) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'exp' => time() + (60 * 60)
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'your-secret-key', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    private function generateRefreshToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $tokenId = $this->generateUUID();

        try {
            $query = "INSERT INTO " . $this->refreshTokenTable . " (id, user_id, token, expires_at) VALUES (:id, :user_id, :token, :expires_at)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $tokenId);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":token", $token);
            $stmt->bindParam(":expires_at", $expiresAt);

            if ($stmt->execute()) {
                return $token;
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->generateRefreshToken($userId);
            }
        }

        return false;
    }

    private function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function logAuthEvent($userId, $action, $ipAddress, $userAgent, $status, $details = null) {
        $logId = $this->generateUUID();
        $detailsJson = $details ? json_encode($details) : null;
        
        $query = "INSERT INTO auth_logs (id, user_id, action, ip_address, user_agent, status, details) 
                  VALUES (:id, :user_id, :action, :ip_address, :user_agent, :status, :details)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $logId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":ip_address", $ipAddress);
        $stmt->bindParam(":user_agent", $userAgent);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":details", $detailsJson);
        
        return $stmt->execute();
    }
} 