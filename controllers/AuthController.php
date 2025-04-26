<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/Mail.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../utils/ResponseUtil.php';
require_once __DIR__ . '/../config/Database.php';

class AuthController {
    private $authService;
    private $mail;
    private $db;

    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->authService = new AuthService($db);
        $this->mail = new Mail();
        $this->db = new Database();
    }

    public function register($data) {
        try {
            // Validate email
            if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                return ResponseUtil::error('Email không hợp lệ');
            }

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data->email]);
            if ($stmt->rowCount() > 0) {
                return ResponseUtil::error('Email đã tồn tại');
            }

            // Generate OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Store OTP in database
            $stmt = $this->db->prepare("INSERT INTO otp_verifications (email, otp, expiry_time) VALUES (?, ?, ?)");
            $stmt->execute([$data->email, $otp, $expiry]);
            
            // Store user data temporarily
            $_SESSION['temp_user'] = [
                'email' => $data->email,
                'password' => password_hash($data->password, PASSWORD_DEFAULT),
                'name' => $data->name
            ];

            // Send OTP email
            $result = $this->mail->sendOTP($data->email, $otp);
            
            if ($result) {
                return ResponseUtil::success([
                    'message' => 'Mã OTP đã được gửi đến email của bạn',
                    'email' => $data->email
                ]);
            } else {
                return ResponseUtil::error('Không thể gửi mã OTP');
            }
        } catch (Exception $e) {
            return ResponseUtil::error('Lỗi: ' . $e->getMessage());
        }
    }

    public function verifyOTP($data) {
        try {
            $email = $data['email'];
            $otp = $data['otp'];
            
            // Check OTP validity
            $stmt = $this->db->prepare(
                "SELECT * FROM otp_verifications 
                WHERE email = ? AND otp = ? AND expiry_time > NOW() 
                AND verified = FALSE 
                ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$email, $otp]);
            
            if ($stmt->rowCount() === 0) {
                return ResponseUtil::error('Mã OTP không hợp lệ hoặc đã hết hạn');
            }

            // Mark OTP as verified
            $stmt = $this->db->prepare(
                "UPDATE otp_verifications SET verified = TRUE 
                WHERE email = ? AND otp = ?"
            );
            $stmt->execute([$email, $otp]);

            // Create user account if temporary data exists
            if (isset($_SESSION['temp_user']) && $_SESSION['temp_user']['email'] === $email) {
                $userData = $_SESSION['temp_user'];
                $stmt = $this->db->prepare(
                    "INSERT INTO users (email, password, name, email_verified) 
                    VALUES (?, ?, ?, TRUE)"
                );
                $stmt->execute([
                    $userData['email'],
                    $userData['password'],
                    $userData['name']
                ]);
                
                unset($_SESSION['temp_user']);
            }

            return ResponseUtil::success([
                'message' => 'Xác thực email thành công'
            ]);
        } catch (Exception $e) {
            return ResponseUtil::error('Lỗi: ' . $e->getMessage());
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin đăng nhập"]);
            return;
        }

        // Lấy thông tin thiết bị và IP nếu có
        $deviceInfo = isset($data->device) ? $data->device : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $result = $this->authService->login($data->email, $data->password, $deviceInfo, $ipAddress, $userAgent);

        if ($result) {
            // Kiểm tra xem có yêu cầu 2FA không
            if (isset($result['requires_2fa']) && $result['requires_2fa']) {
                http_response_code(200);
                echo json_encode(['requires_2fa' => true]);
                return;
            }

            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Email hoặc mật khẩu không chính xác"]);
        }
    }

    public function refreshToken() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->refresh_token)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu refresh token"]);
            return;
        }

        $result = $this->authService->refreshToken($data->refresh_token);

        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Refresh token không hợp lệ"]);
        }
    }

    public function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu email"]);
            return;
        }

        $result = $this->authService->forgotPassword($data->email);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Đã gửi email khôi phục mật khẩu"]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Không tìm thấy email"]);
        }
    }

    public function resetPassword() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->token) || !isset($data->new_password)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin"]);
            return;
        }

        $result = $this->authService->resetPassword($data->token, $data->new_password);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Đã đổi mật khẩu thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Token không hợp lệ hoặc đã hết hạn"]);
        }
    }

    public function googleAuth() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->googleId) || !isset($data->idToken)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin xác thực Google"]);
            return;
        }

        $result = $this->authService->googleAuth([
            'email' => $data->email,
            'firstName' => $data->firstName ?? '',
            'lastName' => $data->lastName ?? '',
            'googleId' => $data->googleId,
            'avatar' => $data->avatar ?? null,
            'idToken' => $data->idToken
        ]);

        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Xác thực Google thất bại"]);
        }
    }

    // Thêm phương thức verify2FA
    public function verify2FA() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->code)) {
            http_response_code(400);
            echo json_encode(["message" => "Thiếu thông tin xác thực"]);
            return;
        }

        $result = $this->authService->verify2FA($data->email, $data->code);

        if ($result) {
            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Mã xác thực không hợp lệ"]);
        }
    }
} 