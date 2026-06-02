<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareUserRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password, array $options = [])
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, $username, $password, array_merge($defaultOptions, $options));
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT id, username, email, first_name, last_name, created_at, status
                FROM users
                WHERE id = :id AND active = 1
                LIMIT 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException('Database query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
