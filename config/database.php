<?php

require_once __DIR__ . '/config.php';

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        global $pdo;

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('PDO connection is not initialized.');
        }

        $this->connection = $pdo;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): bool
    {
        $statement = $this->connection->prepare($sql);
        return $statement->execute($params);
    }

    public function fetchOne(string $sql, array $params = []): array|false
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : false;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
