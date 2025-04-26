<?php
require_once __DIR__ . '/BaseService.php';

class UserService extends BaseService {
    public function __construct($db) {
        parent::__construct($db);
        $this->table_name = "users";
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser($data) {
        try {
            $query = "INSERT INTO users (id, username, email, password, full_name, status, role, email_verified, created_at, updated_at) 
                      VALUES (:id, :username, :email, :password, :full_name, :status, :role, :email_verified, :created_at, :updated_at)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $data['password']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':email_verified', $data['email_verified']);
            $stmt->bindParam(':created_at', $data['created_at']);
            $stmt->bindParam(':updated_at', $data['updated_at']);

            if ($stmt->execute()) {
                return $data['id'];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword($id, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
} 