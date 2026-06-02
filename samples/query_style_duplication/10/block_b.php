<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopUpsertRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);
        if ($this->connection->connect_error) {
            throw new RuntimeException('Connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    public function upsertProduct(array $productData): int
    {
        $sku = $productData['sku'];
        $name = $productData['name'];
        $price = (float)($productData['price'] ?? 0);
        $stock = (int)($productData['stock_quantity'] ?? 0);
        $status = $productData['status'] ?? 'active';

        $stmt = $this->connection->prepare(
            'INSERT INTO products (sku, name, price, stock_quantity, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 name = VALUES(name),
                 price = VALUES(price),
                 stock_quantity = VALUES(stock_quantity),
                 status = VALUES(status),
                 updated_at = NOW()'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('ssdis', $sku, $name, $price, $stock, $status);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $insertId = $this->connection->insert_id;
        $stmt->close();

        return (int)$insertId;
    }

    public function upsertUser(array $userData): int
    {
        $email = $userData['email'];
        $username = $userData['username'];
        $firstName = $userData['first_name'] ?? '';
        $lastName = $userData['last_name'] ?? '';
        $status = $userData['status'] ?? 'active';

        $stmt = $this->connection->prepare(
            'INSERT INTO users (email, username, first_name, last_name, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 username = VALUES(username),
                 first_name = VALUES(first_name),
                 last_name = VALUES(last_name),
                 status = VALUES(status),
                 updated_at = NOW()'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('sssss', $email, $username, $firstName, $lastName, $status);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $insertId = $this->connection->insert_id;
        $stmt->close();

        return (int)$insertId;
    }

    public function upsertInventory(array $inventoryData): bool
    {
        $sku = $inventoryData['sku'];
        $warehouseId = (int)$inventoryData['warehouse_id'];
        $quantity = (int)$inventoryData['quantity'];
        $reserved = (int)($inventoryData['reserved'] ?? 0);

        $stmt = $this->connection->prepare(
            'INSERT INTO inventory (sku, warehouse_id, quantity, reserved, updated_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                 quantity = VALUES(quantity),
                 reserved = VALUES(reserved),
                 updated_at = NOW()'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('siii', $sku, $warehouseId, $quantity, $reserved);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $stmt->close();

        return true;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
