<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

interface TransformationStrategyInterface
{
    public function transform(PDO $pdo, string $query, callable $mapper): array;
    public function transformWithGenerator(PDO $pdo, string $query, callable $mapper): Generator;
}

final class TransformationStrategy implements TransformationStrategyInterface
{
    public function transform(PDO $pdo, string $query, callable $mapper): array
    {
        $stmt = $pdo->query($query);
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $mapper($row);
        }

        return $results;
    }

    public function transformWithGenerator(PDO $pdo, string $query, callable $mapper): Generator
    {
        $stmt = $pdo->query($query);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $mapper($row);
        }
    }
}
