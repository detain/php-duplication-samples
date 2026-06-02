<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use stdClass;

final class ResultMapping
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function mapToClass(string $className): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users');

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new $className();
            foreach ($row as $key => $value) {
                $camelKey = $this->toCamelCase($key);
                if (property_exists($user, $camelKey)) {
                    $user->$camelKey = $value;
                }
            }
            $users[] = $user;
        }

        return $users;
    }

    public function mapToDto(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, created_at FROM users');

        $dtos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dtos[] = new UserDto(
                id: (int)$row['id'],
                name: $row['name'],
                email: $row['email'],
                createdAt: new \DateTimeImmutable($row['created_at'])
            );
        }

        return $dtos;
    }

    public function mapToKeyedArray(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users');

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[$row['id']] = $row;
        }

        return $users;
    }

    public function mapToGrouped(): array
    {
        $stmt = $this->pdo->query('SELECT status, id, name FROM users');

        $grouped = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $grouped[$row['status']][] = $row;
        }

        return $grouped;
    }

    private function toCamelCase(string $str): string
    {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }
}

class UserDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly \DateTimeImmutable $createdAt
    ) {}
}
