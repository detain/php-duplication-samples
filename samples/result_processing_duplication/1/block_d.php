<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoFetchRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email, first_name, last_name FROM users');

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }

        return $users;
    }

    public function getUsersFetchAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email FROM users');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersKeyed(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email FROM users');

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[$row['id']] = $row;
        }

        return $users;
    }

    public function getUsersIndexed(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email FROM users');
        return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    }

    public function getUsersGrouped(): array
    {
        $stmt = $this->pdo->query('SELECT status, id, username FROM users');
        return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    }
}
