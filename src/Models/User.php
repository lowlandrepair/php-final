<?php


require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class User
{
    private ?Database $db;
    private ?PDO $connection;

    public ?int $id = null;
    public ?string $email = null;
    public ?string $passwordHash = null;
    public ?string $fullName = null;
    public ?string $role = null;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
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

        $sql = "INSERT INTO users (email, password_hash, full_name, role) 
                VALUES (:email, :password_hash, :full_name, :role)";

        $params = [
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':full_name' => $fullName,
            ':role' => $role
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

    
    public function findById(int $id): array|false
    {
        $sql = "SELECT id, email, full_name, role, created_at, updated_at 
                FROM users WHERE id = :id LIMIT 1";

        $params = [':id' => $id];

        $result = $this->db->fetchOne($sql, $params);

        return $result !== false ? $result : false;
    }

    
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    
    public function getAll(): array
    {
        $sql = "SELECT id, email, full_name, role, created_at, updated_at 
                FROM users 
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql);
    }

    
    public function update(int $id, string $email, string $fullName, string $role): bool
    {
        $sql = "UPDATE users 
                SET email = :email, full_name = :full_name, role = :role 
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':email' => $email,
            ':full_name' => $fullName,
            ':role' => $role
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return false;
            }
            throw $e;
        }
    }

    
    public function updatePassword(int $id, string $newPassword): bool
    {
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return false;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        if ($passwordHash === false) {
            return false;
        }

        $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";

        $params = [
            ':id' => $id,
            ':password_hash' => $passwordHash
        ];

        $this->db->query($sql, $params);
        return true;
    }

    
    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if ($user && $user['role'] === 'admin') {
            $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND id != :id";
            $result = $this->db->fetchOne($sql, [':id' => $id]);
            if ($result['count'] == 0) {
                return false;
            }
        }

        $sql = "DELETE FROM users WHERE id = :id";

        $params = [':id' => $id];

        $this->db->query($sql, $params);
        return true;
    }

    
    public function countByRole(string $role): int
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = :role";

        $params = [':role' => $role];

        $result = $this->db->fetchOne($sql, $params);

        return $result ? (int)$result['count'] : 0;
    }

    
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    
    public function populate(array $data): void
    {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'id':
                    $this->id = (int)$value;
                    break;
                case 'email':
                    $this->email = $value;
                    break;
                case 'password_hash':
                    $this->passwordHash = $value;
                    break;
                case 'full_name':
                    $this->fullName = $value;
                    break;
                case 'role':
                    $this->role = $value;
                    break;
                case 'created_at':
                    $this->createdAt = $value;
                    break;
                case 'updated_at':
                    $this->updatedAt = $value;
                    break;
            }
        }
    }
}


