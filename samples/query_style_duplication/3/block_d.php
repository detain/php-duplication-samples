<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalProductRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function updatePrice(int $productId, float $newPrice): bool
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        try {
            $affected = $this->connection->update(
                'products',
                [
                    'price' => $newPrice,
                    'updated_at' => new \DateTimeImmutable(),
                ],
                [
                    'id' => $productId,
                    'active' => 1,
                ]
            );

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        try {
            $currentProduct = $this->connection->fetchAssociative(
                'SELECT stock_quantity FROM products WHERE id = ? AND active = 1',
                [$productId]
            );

            if (!$currentProduct) {
                return false;
            }

            $newQuantity = (int)$currentProduct['stock_quantity'] + $quantityChange;

            if ($newQuantity < 0) {
                throw new \RuntimeException('Insufficient stock');
            }

            $affected = $this->connection->update(
                'products',
                [
                    'stock_quantity' => $newQuantity,
                    'updated_at' => new \DateTimeImmutable(),
                ],
                [
                    'id' => $productId,
                    'active' => 1,
                ]
            );

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('Stock update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateByCategory(int $categoryId, array $updates): int
    {
        try {
            $affected = $this->connection->update(
                'products',
                array_merge($updates, ['updated_at' => new \DateTimeImmutable()]),
                [
                    'category_id' => $categoryId,
                    'active' => 1,
                ]
            );

            return $affected;
        } catch (\Exception $e) {
            throw new RuntimeException('Category update failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
