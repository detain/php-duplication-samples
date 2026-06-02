<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

final class CursorProcessing
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function useCursor(): Generator
    {
        $stmt = $this->pdo->query('SELECT id, name, data FROM items');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function useLaravelCursor(): Generator
    {
        $stmt = $this->pdo->query('SELECT id, name, data FROM items');

        foreach ($stmt as $row) {
            yield $row;
        }
    }

    public function useIterator(): iterable
    {
        $stmt = $this->pdo->query('SELECT id, name, data FROM items');

        return new class($stmt) implements Iterator {
            private $stmt;
            private $position = 0;
            private $current;

            public function __construct($stmt)
            {
                $this->stmt = $stmt;
            }

            public function current(): mixed
            {
                return $this->current;
            }

            public function key(): int
            {
                return $this->position;
            }

            public function next(): void
            {
                $this->current = $this->stmt->fetch(PDO::FETCH_ASSOC);
                $this->position++;
            }

            public function rewind(): void
            {
                $this->position = 0;
            }

            public function valid(): bool
            {
                $this->current = $this->stmt->fetch(PDO::FETCH_ASSOC);
                return $this->current !== false;
            }
        };
    }

    public function processWithCallback(callable $callback): void
    {
        $stmt = $this->pdo->query('SELECT id, name, data FROM items');

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $callback($row);
        }
    }
}
