<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareUpsertRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function upsertProduct(array $productData): int
    {
        $sku = $productData['sku'];
        $name = $productData['name'];
        $price = (float)($productData['price'] ?? 0);
        $stock = (int)($productData['stock_quantity'] ?? 0);
        $status = $productData['status'] ?? 'active';

        $sql = 'INSERT INTO products (sku, name, price, stock_quantity, status, created_at, updated_at)
                VALUES (:sku, :name, :price, :stock, :status, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    stock_quantity = VALUES(stock_quantity),
                    status = VALUES(status),
                    updated_at = NOW()';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':sku', $sku);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':price', $price, PDO::PARAM_STR);
            $stmt->bindValue(':stock', $stock, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status);
            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException('Upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertUser(array $userData): int
    {
        $sql = 'INSERT INTO users (email, username, first_name, last_name, status, created_at, updated_at)
                VALUES (:email, :username, :first_name, :last_name, :status, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    username = VALUES(username),
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    status = VALUES(status),
                    updated_at = NOW()';

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
            throw new RuntimeException('User upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertOrCreateProduct(array $productData): array
    {
        $sku = $productData['sku'];

        $sql = 'INSERT INTO products (sku, name, price, stock_quantity, status, created_at, updated_at)
                VALUES (:sku, :name, :price, :stock, :status, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    stock_quantity = stock_quantity + VALUES(stock_quantity),
                    status = VALUES(status),
                    updated_at = NOW()';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':sku', $sku);
            $stmt->bindValue(':name', $productData['name']);
            $stmt->bindValue(':price', $productData['price'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(':stock', $productData['stock_quantity'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':status', $productData['status'] ?? 'active');
            $stmt->execute();

            $id = (int)$this->pdo->lastInsertId();

            $selectSql = 'SELECT * FROM products WHERE sku = :sku';
            $selectStmt = $this->pdo->prepare($selectSql);
            $selectStmt->bindValue(':sku', $sku);
            $selectStmt->execute();

            return $selectStmt->fetch();
        } catch (PDOException $e) {
            throw new RuntimeException('Upsert or create failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function batchUpsert(array $records, string $table): int
    {
        if (empty($records)) {
            return 0;
        }

        $this->pdo->beginTransaction();
        $count = 0;

        try {
            foreach ($records as $record) {
                $this->upsertGeneric($table, $record);
                $count++;
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $count;
    }

    private function upsertGeneric(string $table, array $data): void
    {
        $keys = array_keys($data);
        $setClause = implode(', ', array_map(fn($k) => "{$k} = VALUES({$k})", $keys));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", $keys));
        $columns = implode(', ', $keys);

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})
                ON DUPLICATE KEY UPDATE {$setClause}";

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(":{$key}", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":{$key}", $value);
            }
        }

        $stmt->execute();
    }
}
