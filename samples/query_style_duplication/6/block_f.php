<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderReportRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function countOrdersByStatus(string $status): int
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $result = $qb->select('COUNT(*) as cnt')
                ->from('orders', 'o')
                ->where('o.status = :status')
                ->setParameter('status', $status)
                ->execute()
                ->fetchAssociative();

            return (int)($result['cnt'] ?? 0);
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder count failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sumOrderTotal(int $customerId): float
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $result = $qb->select('COALESCE(SUM(o.total_amount), 0) as total')
                ->from('orders', 'o')
                ->where('o.customer_id = :customer_id')
                ->andWhere($qb->expr()->notIn('o.status', [':cancelled', ':refunded']))
                ->setParameter('customer_id', $customerId)
                ->setParameter('cancelled', 'cancelled')
                ->setParameter('refunded', 'refunded')
                ->execute()
                ->fetchAssociative();

            return (float)($result['total'] ?? 0.0);
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder sum failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrderStats(int $customerId): array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $result = $qb->select(
                    'COUNT(*) as total_orders',
                    'COALESCE(SUM(o.total_amount), 0) as total_spent',
                    'COALESCE(AVG(o.total_amount), 0) as avg_order_value',
                    'MIN(o.created_at) as first_order_date',
                    'MAX(o.created_at) as last_order_date'
                )
                ->from('orders', 'o')
                ->where('o.customer_id = :customer_id')
                ->andWhere('o.status != :cancelled')
                ->setParameter('customer_id', $customerId)
                ->setParameter('cancelled', 'cancelled')
                ->execute()
                ->fetchAssociative();

            return [
                'total_orders' => (int)($result['total_orders'] ?? 0),
                'total_spent' => (float)($result['total_spent'] ?? 0.0),
                'avg_order_value' => (float)($result['avg_order_value'] ?? 0.0),
                'first_order_date' => $result['first_order_date'] ?? null,
                'last_order_date' => $result['last_order_date'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder stats failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getQuarterlySales(int $year, int $quarter): array
    {
        $startMonth = (($quarter - 1) * 3) + 1;
        $endMonth = $startMonth + 2;

        try {
            $qb = $this->connection->createQueryBuilder();

            $result = $qb->select(
                    'COUNT(*) as order_count',
                    'COALESCE(SUM(o.total_amount), 0) as quarterly_total',
                    'COALESCE(AVG(o.total_amount), 0) as avg_order_value'
                )
                ->from('orders', 'o')
                ->where('YEAR(o.created_at) = :year')
                ->andWhere('MONTH(o.created_at) >= :start_month')
                ->andWhere('MONTH(o.created_at) <= :end_month')
                ->andWhere('o.status = :completed')
                ->setParameter('year', $year)
                ->setParameter('start_month', $startMonth)
                ->setParameter('end_month', $endMonth)
                ->setParameter('completed', 'completed')
                ->execute()
                ->fetchAssociative();

            return $result;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder quarterly sales failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
