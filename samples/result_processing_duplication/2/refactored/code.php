<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

interface ResultSetHandlerInterface
{
    public function handle(PDO $pdo, string $query): array;
}

final class ResultSetHandler implements ResultSetHandlerInterface
{
    public function handle(PDO $pdo, string $query): array
    {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function handleWithGenerator(PDO $pdo, string $query): Generator
    {
        $stmt = $pdo->query($query);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function handleWithKeyedResult(PDO $pdo, string $query, string $key): array
    {
        $stmt = $pdo->query($query);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row[$key]] = $row;
        }

        return $results;
    }

    public function handleWithMapping(PDO $pdo, string $query, callable $mapper): array
    {
        $stmt = $pdo->query($query);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $mapper($row);
        }

        return $results;
    }
}
