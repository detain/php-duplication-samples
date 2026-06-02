<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalReportRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function countOrdersByStatus(string $status): int
    {
        try {
            $result = $this->connection->fetchAssociative(
                'SELECT COUNT(*) as cnt FROM orders WHERE status = ?',
                [$status]
            );

            return (int)($result['cnt'] ?? 0);
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL count failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sumOrderTotal(int $customerId): float
    {
        try {
            $result = $this->connection->fetchAssociative(
                'SELECT COALESCE(SUM(total_amount), 0) as total
                 FROM orders
                 WHERE customer_id = ? AND status NOT IN (?, ?)',
                [$customerId, 'cancelled', 'refunded']
            );

            return (float)($result['total'] ?? 0.0);
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL sum failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrderStats(int $customerId): array
    {
        try {
            $result = $this->connection->fetchAssociative(
                'SELECT
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                 FROM orders
                 WHERE customer_id = ? AND status != ?',
                [$customerId, 'cancelled']
            );

            return [
                'total_orders' => (int)($result['total_orders'] ?? 0),
                'total_spent' => (float)($result['total_spent'] ?? 0.0),
                'avg_order_value' => (float)($result['avg_order_value'] ?? 0.0),
                'first_order_date' => $result['first_order_date'] ?? null,
                'last_order_date' => $result['last_order_date'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL stats failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCategorySummary(): array
    {
        try {
            $result = $this->connection->fetchAllAssociative(
                'SELECT
                    c.id,
                    c.name,
                    COUNT(p.id) as product_count,
                    COALESCE(SUM(p.stock_quantity), 0) as total_stock,
                    COALESCE(AVG(p.price), 0) as avg_price
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
                 GROUP BY c.id, c.name
                 ORDER BY product_count DESC'
            );

            return $result;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL category summary failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCustomerLifetimeValue(): array
    {
        try {
            $result = $this->connection->fetchAllAssociative(
                'SELECT
                    customer_id,
                    COUNT(*) as order_count,
                    SUM(total_amount) as lifetime_value,
                    AVG(total_amount) as avg_order_value,
                    MAX(created_at) as last_order_date
                 FROM orders
                 WHERE status NOT IN (?, ?)
                 GROUP BY customer_id
                 ORDER BY lifetime_value DESC
                 LIMIT 100',
                ['cancelled', 'refunded']
            );

            return $result;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL LTV query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
