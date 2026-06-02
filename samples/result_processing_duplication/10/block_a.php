<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

final class ResultFiltering
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function filterWithFetchAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status, price FROM products');

        return array_filter(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            fn($row) => $row['status'] === 'active' && $row['price'] > 100
        );
    }

    public function filterWithGenerator(): Generator
    {
        $stmt = $this->pdo->query('SELECT id, name, status, price FROM products');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['status'] === 'active' && $row['price'] > 100) {
                yield $row;
            }
        }
    }

    public function filterWithIterator(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status, price FROM products');

        $filtered = [];
        foreach ($stmt as $row) {
            if ($row['status'] === 'active') {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    public function filterWithCallback(array $criteria): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status, price FROM products');

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($criteria as $field => $value) {
            $results = array_filter($results, fn($row) => $row[$field] === $value);
        }

        return array_values($results);
    }

    public function filterWithGeneratorAndCallback(callable $filter): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status, price FROM products');

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($filter($row)) {
                $results[] = $row;
            }
        }

        return $results;
    }
}
