<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Database {
    private $host = "localhost";
    private $db_name = "brainy";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            return null;
        }
    }
} 