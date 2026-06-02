<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareProductRepository
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

    public function updatePrice(int $productId, float $newPrice): bool
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        $sql = 'UPDATE products
                SET price = :price, updated_at = NOW()
                WHERE id = :id AND active = 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':price', $newPrice, PDO::PARAM_STR);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('Update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        $sql = 'UPDATE products
                SET stock_quantity = stock_quantity + :change,
                    updated_at = NOW()
                WHERE id = :id
                  AND active = 1
                  AND stock_quantity + :change >= 0';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':change', $quantityChange, PDO::PARAM_INT);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('Stock update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateCategory(int $productId, int $categoryId): bool
    {
        $sql = 'UPDATE products
                SET category_id = :category_id, updated_at = NOW()
                WHERE id = :id AND active = 1';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('Category update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function bulkUpdatePrices(array $priceUpdates): int
    {
        $this->pdo->beginTransaction();
        $totalAffected = 0;

        try {
            $sql = 'UPDATE products SET price = :price, updated_at = NOW() WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);

            foreach ($priceUpdates as $update) {
                $stmt->bindValue(':price', $update['price'], PDO::PARAM_STR);
                $stmt->bindValue(':id', $update['id'], PDO::PARAM_INT);
                $stmt->execute();
                $totalAffected += $stmt->rowCount();
            }

            $this->pdo->commit();

            return $totalAffected;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
