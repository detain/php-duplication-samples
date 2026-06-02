<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

interface StreamStrategyInterface
{
    public function stream(PDO $pdo, string $query): Generator;
    public function chunk(PDO $pdo, string $query, int $size, callable $processor): void;
}

final class StreamStrategy implements StreamStrategyInterface
{
    public function stream(PDO $pdo, string $query): Generator
    {
        $stmt = $pdo->query($query);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function chunk(PDO $pdo, string $query, int $size, callable $processor): void
    {
        $offset = 0;

        while (true) {
            $stmt = $pdo->prepare($query . " LIMIT {$size} OFFSET {$offset}");
            $stmt->execute();
            $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($chunk)) {
                break;
            }

            $processor($chunk);
            $offset += $size;
        }
    }
}
