<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoQueryUserRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password, array $options = [])
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $this->pdo = new PDO($dsn, $username, $password, array_merge($defaultOptions, $options));
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT id, username, email, first_name, last_name, created_at, status
                FROM users
                WHERE id = {$id} AND active = 1
                LIMIT 1";

        try {
            $stmt = $this->pdo->query($sql);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException('Database query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
