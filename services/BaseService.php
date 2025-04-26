<?php

class BaseService {
    protected $conn;
    protected $table_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $fields = [];
        $values = [];
        $params = [];

        if (!isset($data['id'])) {
            $data['id'] = $this->generateUUID();
        }

        foreach ($data as $key => $value) {
            $fields[] = $key;
            $params[] = ":" . $key;
        }

        $query = "INSERT INTO " . $this->table_name . " (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $params) . ")";
        $stmt = $this->conn->prepare($query);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":" . $key, $value);
        }

        if ($stmt->execute()) {
            return $data['id'];
        }
        return false;
    }

    public function read($id = null) {
        $query = "SELECT * FROM " . $this->table_name;
        if ($id) {
            $query .= " WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($id) {
            $stmt->bindParam(":id", $id);
        }
        
        $stmt->execute();
        return $id ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET ";
        $fields = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = $key . " = :" . $key;
            }
        }

        $query .= implode(", ", $fields);
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $stmt->bindValue(":" . $key, $value);
            }
        }

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    protected function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    protected function handleError($e) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
        
        if ($e instanceof PDOException) {
            // Log database errors
            error_log("Database Error: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Database operation failed',
                'code' => $e->getCode()
            ];
        }
        
        return [
            'error' => true,
            'message' => 'Internal server error',
            'code' => 500
        ];
    }
} 