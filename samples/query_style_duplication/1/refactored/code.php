<?php
declare(strict_types=1);

namespace App\Repository;

use App\Repository\UserFetcherInterface;
use App\Repository\UserNotFoundException;
use PDO;
use PDOException;
use RuntimeException;

final class UserRepository implements UserFetcherInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'users')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function findById(int $id): ?array
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }

        $sql = "SELECT id, username, email, first_name, last_name, created_at, status
                FROM {$this->tableName}
                WHERE id = :id AND active = 1
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to retrieve user with ID {$id}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function findActiveById(int $id): ?array
    {
        return $this->findById($id);
    }

    public function findByUsername(string $username): ?array
    {
        if (empty(trim($username))) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        $sql = "SELECT id, username, email, first_name, last_name, created_at, status
                FROM {$this->tableName}
                WHERE username = :username AND active = 1
                LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':username', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to retrieve user '{$username}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function exists(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $sql = "SELECT 1 FROM {$this->tableName} WHERE id = :id AND active = 1 LIMIT 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
}
