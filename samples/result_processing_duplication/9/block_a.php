<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use Generator;

final class ResultTransformation
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function transformWithArrayMap(): array
    {
        $stmt = $this->pdo->query('SELECT id, first_name, last_name, email FROM users');

        return array_map(
            fn($row) => [
                'id' => (int)$row['id'],
                'full_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'email' => strtolower($row['email']),
            ],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function transformWithForeach(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, price FROM products');

        $transformed = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $transformed[] = [
                'id' => (int)$row['id'],
                'name' => ucwords(strtolower($row['name'])),
                'price' => (float)$row['price'],
                'formatted_price' => number_format((float)$row['price'], 2),
            ];
        }

        return $transformed;
    }

    public function transformWithGenerator(): Generator
    {
        $stmt = $this->pdo->query('SELECT id, name, description FROM products');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield [
                'id' => (int)$row['id'],
                'name' => trim($row['name']),
                'description' => trim($row['description']),
                'word_count' => str_word_count($row['description']),
            ];
        }
    }

    public function transformWithReduce(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, price FROM products');

        return array_reduce(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            function ($carry, $row) {
                $carry[$row['id']] = [
                    'name' => $row['name'],
                    'price' => (float)$row['price'],
                    'taxed_price' => (float)$row['price'] * 1.2,
                ];
                return $carry;
            },
            []
        );
    }
}
