<?php
require_once __DIR__ . '/BaseService.php';

class EmailService extends BaseService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct($db) {
        parent::__construct($db);
        
        // Lấy cấu hình email từ database
        $this->loadEmailConfig();
    }
    
    private function loadEmailConfig() {
        $query = "SELECT name, value FROM app_configs WHERE name LIKE 'MAIL_%'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch ($row['name']) {
                case 'MAIL_HOST':
                    $this->smtpHost = $row['value'];
                    break;
                case 'MAIL_PORT':
                    $this->smtpPort = $row['value'];
                    break;
                case 'MAIL_USERNAME':
                    $this->smtpUsername = $row['value'];
                    break;
                case 'MAIL_PASSWORD':
                    $this->smtpPassword = $row['value'];
                    break;
                case 'MAIL_FROM_ADDRESS':
                    $this->fromEmail = $row['value'];
                    break;
                case 'MAIL_FROM_NAME':
                    $this->fromName = $row['value'];
                    break;
            }
        }
    }
    
    public function sendEmail($to, $subject, $body, $isHtml = true) {
        // Kiểm tra cấu hình email
        if (!$this->smtpHost || !$this->smtpUsername || !$this->smtpPassword) {
            error_log("Email configuration is missing");
            return false;
        }
        
        // Headers
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        
        if ($isHtml) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        // Gửi email (sử dụng hàm mail() của PHP)
        // Trong thực tế nên sử dụng thư viện như PHPMailer
        $result = mail($to, $subject, $body, $headers);
        
        if ($result) {
            $this->logEmail($to, $subject, 'success');
        } else {
            $this->logEmail($to, $subject, 'failed');
        }
        
        return $result;
    }
    
    public function sendVerificationEmail($user, $token) {
        $subject = "Xác thực tài khoản Brainy";
        
        $verifyUrl = "https://brainy.example.com/verify-email?token={$token}";
        
        $body = "
        <html>
        <head>
            <title>Xác thực email</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2>Xác thực tài khoản Brainy của bạn</h2>
                <p>Xin chào {$user['full_name']},</p>
                <p>Cảm ơn bạn đã đăng ký tài khoản tại Brainy. Để hoàn tất quá trình đăng ký, vui lòng xác thực email của bạn bằng cách nhấp vào liên kết bên dưới:</p>
                <p><a href='{$verifyUrl}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Xác thực email</a></p>
                <p>Hoặc sử dụng liên kết này: <a href='{$verifyUrl}'>{$verifyUrl}</a></p>
                <p>Liên kết này sẽ hết hạn sau 24 giờ.</p>
                <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
                <p>Trân trọng,<br>Đội ngũ Brainy</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user['email'], $subject, $body);
    }
    
    public function sendPasswordResetEmail($user, $token) {
        $subject = "Đặt lại mật khẩu Brainy";
        
        $resetUrl = "https://brainy.example.com/reset-password?token={$token}";
        
        $body = "
        <html>
        <head>
            <title>Đặt lại mật khẩu</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2>Đặt lại mật khẩu Brainy của bạn</h2>
                <p>Xin chào {$user['full_name']},</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Nhấp vào nút bên dưới để đặt lại mật khẩu:</p>
                <p><a href='{$resetUrl}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Đặt lại mật khẩu</a></p>
                <p>Hoặc sử dụng liên kết này: <a href='{$resetUrl}'>{$resetUrl}</a></p>
                <p>Liên kết này sẽ hết hạn sau 1 giờ.</p>
                <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
                <p>Trân trọng,<br>Đội ngũ Brainy</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user['email'], $subject, $body);
    }
    
    private function logEmail($to, $subject, $status) {
        try {
            $query = "INSERT INTO email_logs (id, recipient, subject, status, created_at) VALUES (:id, :recipient, :subject, :status, NOW())";
            $stmt = $this->conn->prepare($query);
            
            $id = $this->generateUUID();
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":recipient", $to);
            $stmt->bindParam(":subject", $subject);
            $stmt->bindParam(":status", $status);
            
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error logging email: " . $e->getMessage());
        }
    }
}