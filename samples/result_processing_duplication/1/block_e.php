<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use RuntimeException;

final class PdoFetchObjectRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email, first_name, last_name FROM users');

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $users[] = $row;
        }

        return $users;
    }

    public function getUsersAsClass(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email, first_name, last_name FROM users');

        $users = [];
        while ($row = $stmt->fetchObject(UserDTO::class)) {
            $users[] = $row;
        }

        return $users;
    }

    public function getUsersCallable(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email FROM users');

        return $stmt->fetchAll(PDO::FETCH_FUNC, function ($id, $username, $email) {
            return [
                'id' => $id,
                'username' => strtolower($username),
                'email' => strtolower($email),
            ];
        });
    }
}

class UserDTO
{
    public int $id;
    public string $username;
    public string $email;
    public string $first_name;
    public string $last_name;
}
