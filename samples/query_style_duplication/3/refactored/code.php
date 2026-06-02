<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class ProductRepository implements ProductRepositoryInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'products')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function updatePrice(int $productId, float $newPrice): bool
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        if ($productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be positive');
        }

        $sql = "UPDATE {$this->tableName}
                SET price = :price, updated_at = NOW()
                WHERE id = :id AND active = 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':price', $newPrice, PDO::PARAM_STR);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to update price for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        if ($productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be positive');
        }

        $sql = "UPDATE {$this->tableName}
                SET stock_quantity = stock_quantity + :change,
                    updated_at = NOW()
                WHERE id = :id
                  AND active = 1
                  AND stock_quantity + :change >= 0";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':change', $quantityChange, PDO::PARAM_INT);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to update stock for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function updateField(int $productId, string $field, mixed $value): bool
    {
        $allowedFields = ['price', 'stock_quantity', 'category_id', 'status', 'name'];
        if (!in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException("Field '{$field}' is not allowed");
        }

        $sql = "UPDATE {$this->tableName}
                SET {$field} = :value, updated_at = NOW()
                WHERE id = :id AND active = 1";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':value', $value);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to update {$field} for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
