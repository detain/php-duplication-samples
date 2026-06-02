<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralUpsertRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = mysqli_connect($host, $username, $password, $database);
        if ($this->connection === false) {
            throw new RuntimeException('Connection failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function upsertProduct(array $productData): int
    {
        $sku = mysqli_real_escape_string($this->connection, $productData['sku']);
        $name = mysqli_real_escape_string($this->connection, $productData['name']);
        $price = (float)($productData['price'] ?? 0);
        $stock = (int)($productData['stock_quantity'] ?? 0);
        $status = mysqli_real_escape_string($this->connection, $productData['status'] ?? 'active');

        $sql = "INSERT INTO products (sku, name, price, stock_quantity, status, created_at, updated_at)
                VALUES ('{$sku}', '{$name}', {$price}, {$stock}, '{$status}', NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    price = VALUES(price),
                    stock_quantity = VALUES(stock_quantity),
                    status = VALUES(status),
                    updated_at = NOW()";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Upsert failed: ' . mysqli_error($this->connection));
        }

        return (int)mysqli_insert_id($this->connection);
    }

    public function upsertPrice(string $sku, float $price): bool
    {
        $sku = mysqli_real_escape_string($this->connection, $sku);

        $sql = "INSERT INTO product_prices (sku, price, updated_at)
                VALUES ('{$sku}', {$price}, NOW())
                ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    updated_at = NOW()";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Price upsert failed: ' . mysqli_error($this->connection));
        }

        return mysqli_affected_rows($this->connection) > 0;
    }

    public function batchUpsertProducts(array $products): int
    {
        if (empty($products)) {
            return 0;
        }

        $this->connection->begin_transaction();
        $upsertedCount = 0;

        try {
            foreach ($products as $product) {
                $sku = mysqli_real_escape_string($this->connection, $product['sku']);
                $name = mysqli_real_escape_string($this->connection, $product['name']);
                $price = (float)($product['price'] ?? 0);
                $stock = (int)($product['stock_quantity'] ?? 0);

                $sql = "INSERT INTO products (sku, name, price, stock_quantity, created_at, updated_at)
                        VALUES ('{$sku}', '{$name}', {$price}, {$stock}, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                            name = VALUES(name),
                            price = VALUES(price),
                            stock_quantity = VALUES(stock_quantity),
                            updated_at = NOW()";

                mysqli_query($this->connection, $sql);
                $upsertedCount++;
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $upsertedCount;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
