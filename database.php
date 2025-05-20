<?php
require_once 'config.php';

class Database {
    // Existing database connection code
    
    public function register($name, $email, $password, $phone) {
        $sql = 'INSERT INTO users (name, email, password, phone_number) VALUES (:name, :email, :password, :phone)';
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => $phone
        ]);
    }
    
    public function getUserById($id) {
        $sql = 'SELECT * FROM users WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    // Add other necessary methods for 2FA and password reset
}
