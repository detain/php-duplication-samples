<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;

final class IterationStrategy
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function iterate(string $sql, string $mode = 'assoc'): array
    {
        $stmt = $this->pdo->query($sql);

        $fetchMode = match ($mode) {
            'num' => PDO::FETCH_NUM,
            'obj' => PDO::FETCH_OBJ,
            default => PDO::FETCH_ASSOC,
        };

        $results = [];
        while ($row = $stmt->fetch($fetchMode)) {
            $results[] = $row;
        }

        return $results;
    }

    public function iterateWithKey(string $sql, string $keyField): array
    {
        $stmt = $this->pdo->query($sql);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row[$keyField]] = $row;
        }

        return $results;
    }

    public function iterateAll(string $sql): array
    {
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
