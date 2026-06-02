<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

final class ResultSetProcessor
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function processWithMysqliFetchArray(): array
    {
        $result = $this->pdo->query('SELECT id, name, email FROM users');

        $users = [];
        while ($row = $result->fetch(\PDO::FETCH_NUM)) {
            $users[] = [
                'id' => (int)$row[0],
                'name' => $row[1],
                'email' => $row[2],
            ];
        }

        return $users;
    }

    public function processWithMysqliFetchAssoc(): array
    {
        $result = $this->pdo->query('SELECT id, name, email FROM users');

        $users = [];
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }

        return $users;
    }

    public function processWithMysqliFetchObject(): array
    {
        $result = $this->pdo->query('SELECT id, name, email FROM users');

        $users = [];
        while ($row = $result->fetch(\PDO::FETCH_OBJ)) {
            $users[] = $row;
        }

        return $users;
    }

    public function processWithFetchAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function processWithGenerator(): Generator
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users');

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}
