<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralProductRepository
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

    public function updatePrice(int $productId, float $newPrice): bool
    {
        $productId = mysqli_real_escape_string($this->connection, (string)$productId);
        $newPrice = mysqli_real_escape_string($this->connection, (string)$newPrice);

        $sql = "UPDATE products
                SET price = {$newPrice}, updated_at = NOW()
                WHERE id = {$productId}
                  AND active = 1";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Update failed: ' . mysqli_error($this->connection));
        }

        $affected = mysqli_affected_rows($this->connection);
        return $affected > 0;
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        $productId = (int)$productId;
        $quantityChange = (int)$quantityChange;

        $sql = "UPDATE products
                SET stock_quantity = stock_quantity + {$quantityChange},
                    updated_at = NOW()
                WHERE id = {$productId}
                  AND active = 1
                  AND stock_quantity + {$quantityChange} >= 0";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Stock update failed: ' . mysqli_error($this->connection));
        }

        return mysqli_affected_rows($this->connection) > 0;
    }

    public function updateStatus(int $productId, string $status): bool
    {
        $productId = mysqli_real_escape_string($this->connection, (string)$productId);
        $status = mysqli_real_escape_string($this->connection, $status);

        $allowedStatuses = ['active', 'inactive', 'discontinued', 'out_of_stock'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException('Invalid status value');
        }

        $sql = "UPDATE products
                SET status = '{$status}', updated_at = NOW()
                WHERE id = {$productId}";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Status update failed: ' . mysqli_error($this->connection));
        }

        return mysqli_affected_rows($this->connection) > 0;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
