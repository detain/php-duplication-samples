<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderOrderDetailRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $order = $qb->select(
                    'o.id', 'o.order_number', 'o.customer_id', 'o.total_amount', 'o.status',
                    'o.created_at', 'o.shipping_address',
                    'c.first_name', 'c.last_name', 'c.email',
                    'p.name as payment_method', 'p.provider_reference'
                )
                ->from('orders', 'o')
                ->innerJoin('o', 'customers', 'c', 'o.customer_id = c.id')
                ->leftJoin('o', 'payments', 'p', 'o.id = p.order_id')
                ->where('o.id = :order_id')
                ->setParameter('order_id', $orderId)
                ->execute()
                ->fetchAssociative();

            if (!$order) {
                return null;
            }

            $itemsQb = $this->connection->createQueryBuilder();
            $items = $itemsQb->select('oi.id', 'oi.product_id', 'oi.quantity', 'oi.unit_price', 'pr.name', 'pr.sku')
                ->from('order_items', 'oi')
                ->innerJoin('oi', 'products', 'pr', 'oi.product_id = pr.id')
                ->where('oi.order_id = :order_id')
                ->setParameter('order_id', $orderId)
                ->execute()
                ->fetchAllAssociative();

            $order['items'] = $items;

            return $order;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCustomerOrderHistory(int $customerId, int $limit = 20): array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $results = $qb->select(
                    'o.id', 'o.order_number', 'o.total_amount', 'o.status', 'o.created_at',
                    'c.first_name', 'c.last_name',
                    'COUNT(oi.id) as item_count',
                    'pay.name as last_payment_method'
                )
                ->from('orders', 'o')
                ->innerJoin('o', 'customers', 'c', 'o.customer_id = c.id')
                ->leftJoin('o', 'order_items', 'oi', 'o.id = oi.order_id')
                ->leftJoin('o', 'payments', 'pay', 'o.id = pay.order_id')
                ->where('o.customer_id = :customer_id')
                ->setParameter('customer_id', $customerId)
                ->groupBy('o.id', 'o.order_number', 'o.total_amount', 'o.status', 'o.created_at', 'c.first_name', 'c.last_name', 'pay.name')
                ->orderBy('o.created_at', 'DESC')
                ->setMaxResults($limit)
                ->execute()
                ->fetchAllAssociative();

            return $results;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder history query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrdersWithPagination(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        try {
            $countQb = $this->connection->createQueryBuilder();
            $total = $countQb->select('COUNT(*)')
                ->from('orders', 'o')
                ->innerJoin('o', 'customers', 'c', 'o.customer_id = c.id')
                ->execute()
                ->fetchAssociative();

            $qb = $this->connection->createQueryBuilder();

            $orders = $qb->select(
                    'o.id', 'o.order_number', 'o.total_amount', 'o.status', 'o.created_at',
                    'c.first_name', 'c.last_name', 'c.email'
                )
                ->from('orders', 'o')
                ->innerJoin('o', 'customers', 'c', 'o.customer_id = c.id')
                ->orderBy('o.created_at', 'DESC')
                ->setMaxResults($perPage)
                ->setFirstResult($offset)
                ->execute()
                ->fetchAllAssociative();

            return [
                'orders' => $orders,
                'total' => (int)($total['COUNT(*)'] ?? 0),
                'page' => $page,
                'per_page' => $perPage,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder pagination query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
