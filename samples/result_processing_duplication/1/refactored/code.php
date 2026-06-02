<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use RuntimeException;

interface UserFetcherInterface
{
    public function fetchAll(): array;
    public function fetchById(int $id): ?array;
    public function fetchByIds(array $ids): array;
}

final class UserRepository implements UserFetcherInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'users')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function fetchAll(): array
    {
        $stmt = $this->pdo->query("SELECT id, username, email, first_name, last_name FROM {$this->tableName}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, first_name, last_name FROM {$this->tableName} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function fetchByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT id, username, email FROM {$this->tableName} WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    }

    public function fetchByStatus(string $status): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, status FROM {$this->tableName} WHERE status = :status");
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchWithGenerator(): \Generator
    {
        $stmt = $this->pdo->query("SELECT id, username, email FROM {$this->tableName}");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}
