<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

interface FilterStrategyInterface
{
    public function filter(PDO $pdo, string $query, callable $filter): array;
    public function filterWithGenerator(PDO $pdo, string $query, callable $filter): Generator;
}

final class FilterStrategy implements FilterStrategyInterface
{
    public function filter(PDO $pdo, string $query, callable $filter): array
    {
        $stmt = $pdo->query($query);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($filter($row)) {
                $results[] = $row;
            }
        }

        return $results;
    }

    public function filterWithGenerator(PDO $pdo, string $query, callable $filter): Generator
    {
        $stmt = $pdo->query($query);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($filter($row)) {
                yield $row;
            }
        }
    }

    public function filterWithCriteria(PDO $pdo, string $query, array $criteria): array
    {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($criteria as $field => $value) {
            $results = array_filter($results, fn($row) => $row[$field] === $value);
        }

        return array_values($results);
    }
}
