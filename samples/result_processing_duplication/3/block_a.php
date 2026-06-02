<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;

final class ResultIteration
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function iterateForeach(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status FROM products');
        $products = [];

        foreach ($stmt as $row) {
            $products[] = $row;
        }

        return $products;
    }

    public function iterateWhile(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status FROM products');
        $products = [];

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $products[] = $row;
        }

        return $products;
    }

    public function iterateWithKeys(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status FROM products');
        $products = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[$row['id']] = $row;
        }

        return $products;
    }

    public function iterateAndMap(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status FROM products');

        return array_map(function ($row) {
            $row['name_upper'] = strtoupper($row['name']);
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function iterateAndFilter(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, status FROM products');

        return array_filter($stmt->fetchAll(PDO::FETCH_ASSOC), function ($row) {
            return $row['status'] === 'active';
        });
    }
}
