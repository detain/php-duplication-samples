<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

interface CursorStrategyInterface
{
    public function cursor(PDO $pdo, string $query): Generator;
}

final class CursorStrategy implements CursorStrategyInterface
{
    public function cursor(PDO $pdo, string $query): Generator
    {
        $stmt = $pdo->query($query);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function cursorWithProcess(PDO $pdo, string $query, callable $processor): void
    {
        foreach ($this->cursor($pdo, $query) as $row) {
            $processor($row);
        }
    }

    public function cursorWithLimit(PDO $pdo, string $query, int $limit): Generator
    {
        $stmt = $pdo->query($query);
        $count = 0;

        while ($count < $limit && ($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            yield $row;
            $count++;
        }
    }
}
