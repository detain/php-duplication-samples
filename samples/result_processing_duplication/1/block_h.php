<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class GeneratorResultRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUsersGenerator(): \Generator
    {
        $stmt = $this->pdo->query('SELECT id, username, email, first_name, last_name FROM users');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function getUsersArray(): array
    {
        return iterator_to_array($this->getUsersGenerator());
    }

    public function getUsersChunked(int $chunkSize): array
    {
        $chunks = [];
        $currentChunk = [];

        foreach ($this->getUsersGenerator() as $user) {
            $currentChunk[] = $user;

            if (count($currentChunk) >= $chunkSize) {
                $chunks[] = $currentChunk;
                $currentChunk = [];
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    public function processUsers(callable $processor): void
    {
        foreach ($this->getUsersGenerator() as $user) {
            $processor($user);
        }
    }

    public function filterUsers(callable $filter): array
    {
        $filtered = [];

        foreach ($this->getUsersGenerator() as $user) {
            if ($filter($user)) {
                $filtered[] = $user;
            }
        }

        return $filtered;
    }
}
