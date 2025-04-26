<?php
class Mail {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $fromAddress;
    private $fromName;

    public function __construct() {
        $this->host = getenv('MAIL_HOST');
        $this->port = getenv('MAIL_PORT');
        $this->username = getenv('MAIL_USERNAME');
        $this->password = getenv('MAIL_PASSWORD');
        $this->encryption = getenv('MAIL_ENCRYPTION');
        $this->fromAddress = getenv('MAIL_FROM_ADDRESS');
        $this->fromName = getenv('MAIL_FROM_NAME');
    }

    public function send($to, $subject, $message) {
        $headers = [
            'From' => $this->fromName . ' <' . $this->fromAddress . '>',
            'Reply-To' => $this->fromAddress,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];

        // Convert headers array to string
        $headerStr = '';
        foreach ($headers as $key => $value) {
            $headerStr .= $key . ': ' . $value . "\r\n";
        }

        // Additional mail settings
        $additional_parameters = '-f ' . $this->fromAddress;

        // Send email
        return mail($to, $subject, $message, $headerStr, $additional_parameters);
    }

    public function sendOTP($to, $otp) {
        $subject = "Brainy - Xác thực email của bạn";
        $message = "
        <html>
        <head>
            <title>Xác thực tài khoản Brainy</title>
        </head>
        <body>
            <h2>Chào mừng bạn đến với Brainy!</h2>
            <p>Mã OTP của bạn là: <strong style='font-size: 20px; color: #4285f4;'>{$otp}</strong></p>
            <p>Mã này sẽ hết hạn trong vòng 5 phút.</p>
            <p>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
            <br>
            <p>Trân trọng,</p>
            <p>Đội ngũ Brainy</p>
        </body>
        </html>
        ";

        return $this->send($to, $subject, $message);
    }
}