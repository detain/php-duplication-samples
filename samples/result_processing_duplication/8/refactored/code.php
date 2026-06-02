<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;

interface AggregationStrategyInterface
{
    public function aggregate(PDO $pdo, string $query, string $groupBy): array;
}

final class AggregationStrategy implements AggregationStrategyInterface
{
    public function aggregate(PDO $pdo, string $query, string $groupBy): array
    {
        $stmt = $pdo->query($query);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row[$groupBy];
            unset($row[$groupBy]);
            $results[$key] = $row;
        }

        return $results;
    }

    public function aggregateMultiple(PDO $pdo, string $query): array
    {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    }
}
