<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopProductRepository
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

    public function updatePrice(int $productId, float $newPrice): bool
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        $stmt = $this->connection->prepare(
            'UPDATE products
             SET price = ?, updated_at = NOW()
             WHERE id = ? AND active = 1'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('di', $newPrice, $productId);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE products
             SET stock_quantity = stock_quantity + ?,
                 updated_at = NOW()
             WHERE id = ?
               AND active = 1
               AND stock_quantity + ? >= 0'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('iii', $quantityChange, $productId, $quantityChange);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }

    public function updateMultiple(array $updates): int
    {
        $affectedCount = 0;
        $this->connection->begin_transaction();

        try {
            foreach ($updates as $update) {
                $productId = (int)($update['id'] ?? 0);
                $field = $update['field'] ?? '';
                $value = $update['value'] ?? null;

                if ($productId <= 0 || $field === '' || $value === null) {
                    throw new \InvalidArgumentException('Invalid update parameters');
                }

                $sql = "UPDATE products SET {$field} = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->bind_param('si', $value, $productId);
                $stmt->execute();
                $affectedCount += $stmt->affected_rows;
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $affectedCount;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
