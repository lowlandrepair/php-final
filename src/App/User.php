<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(string $email, string $password, string $fullName, string $role = 'viewer'): int|false
    {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        if ($passwordHash === false) {
            return false;
        }

        $sql = "INSERT INTO users (email, password_hash, full_name, role) VALUES (:email, :password_hash, :full_name, :role)";
        $params = [
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':full_name' => $fullName,
            ':role' => $role,
        ];

        try {
            $this->db->query($sql, $params);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return false;
            }
            throw $e;
        }
    }

    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $params = [':email' => $email];
        $result = $this->db->fetchOne($sql, $params);
        return $result !== false ? $result : false;
    }
}
