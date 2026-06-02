<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

final class ResultStreamProcessor
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function streamResults(): Generator
    {
        $stmt = $this->pdo->prepare('SELECT id, data, created_at FROM large_table');
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function processInChunks(callable $processor, int $chunkSize = 100): void
    {
        $offset = 0;

        while (true) {
            $stmt = $this->pdo->prepare(
                'SELECT id, data, created_at FROM large_table LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $chunk = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($chunk)) {
                break;
            }

            $processor($chunk);
            $offset += $chunkSize;
        }
    }

    public function lazyLoad(string $table, int $batchSize = 50): \Generator
    {
        $lastId = 0;

        while (true) {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$table} WHERE id > :last_id ORDER BY id LIMIT :limit"
            );
            $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $lastId = $row['id'];
                yield $row;
            }
        }
    }

    public function bufferedQuery(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM products');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unbufferedQuery(): array
    {
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            $stmt = $this->pdo->query('SELECT * FROM large_table');

            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $row;
            }

            return $results;
        } finally {
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }
}
