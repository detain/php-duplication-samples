<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class UpsertRepository implements UpsertRepositoryInterface
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(PDO $pdo, string $prefix = '')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function upsertProduct(array $productData): int
    {
        $sql = "INSERT INTO {$this->prefix}products (sku, name, price, stock_quantity, status, created_at, updated_at)
                VALUES (:sku, :name, :price, :stock, :status, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    stock_quantity = VALUES(stock_quantity),
                    status = VALUES(status),
                    updated_at = NOW()";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':sku', $productData['sku']);
            $stmt->bindValue(':name', $productData['name']);
            $stmt->bindValue(':price', $productData['price'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(':stock', $productData['stock_quantity'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':status', $productData['status'] ?? 'active');
            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Product upsert failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function upsertUser(array $userData): int
    {
        $sql = "INSERT INTO {$this->prefix}users (email, username, first_name, last_name, status, created_at, updated_at)
                VALUES (:email, :username, :first_name, :last_name, :status, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    username = VALUES(username),
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    status = VALUES(status),
                    updated_at = NOW()";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':email', $userData['email']);
            $stmt->bindValue(':username', $userData['username']);
            $stmt->bindValue(':first_name', $userData['first_name'] ?? '');
            $stmt->bindValue(':last_name', $userData['last_name'] ?? '');
            $stmt->bindValue(':status', $userData['status'] ?? 'active');
            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "User upsert failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function upsertOrIncrement(string $table, string $uniqueKey, mixed $uniqueValue, array $data): bool
    {
        $keys = array_keys($data);
        $setClause = implode(', ', array_map(fn($k) => "{$k} = VALUES({$k})", $keys));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", $keys));
        $columns = implode(', ', $keys);

        $sql = "INSERT INTO {$this->prefix}{$table} ({$columns}) VALUES ({$placeholders})
                ON DUPLICATE KEY UPDATE {$setClause}";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Upsert failed on {$table}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
