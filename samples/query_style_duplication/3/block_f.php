<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderProductRepository
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
            $qb = $this->connection->createQueryBuilder();

            $affected = $qb->update('products', 'p')
                ->set('p.price', ':price')
                ->set('p.updated_at', ':updated')
                ->where('p.id = :id')
                ->andWhere('p.active = :active')
                ->setParameters([
                    'price' => $newPrice,
                    'updated' => new \DateTimeImmutable(),
                    'id' => $productId,
                    'active' => 1,
                ])
                ->execute();

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $affected = $qb->update('products', 'p')
                ->set('p.stock_quantity', 'p.stock_quantity + :change')
                ->set('p.updated_at', ':updated')
                ->where('p.id = :id')
                ->andWhere('p.active = :active')
                ->andWhere('p.stock_quantity + :change >= 0')
                ->setParameters([
                    'change' => $quantityChange,
                    'updated' => new \DateTimeImmutable(),
                    'id' => $productId,
                    'active' => 1,
                ])
                ->execute();

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder stock update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function massUpdateCategory(int $oldCategoryId, int $newCategoryId): int
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            return $qb->update('products', 'p')
                ->set('p.category_id', ':new_category')
                ->set('p.updated_at', ':updated')
                ->where('p.category_id = :old_category')
                ->andWhere('p.active = :active')
                ->setParameters([
                    'new_category' => $newCategoryId,
                    'old_category' => $oldCategoryId,
                    'updated' => new \DateTimeImmutable(),
                    'active' => 1,
                ])
                ->execute();
        } catch (\Exception $e) {
            throw new RuntimeException('Mass update failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
