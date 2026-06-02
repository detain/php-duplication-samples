<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalOrderDetailRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        try {
            $order = $this->connection->fetchAssociative(
                'SELECT o.id, o.order_number, o.customer_id, o.total_amount, o.status,
                        o.created_at, o.shipping_address,
                        c.first_name, c.last_name, c.email,
                        p.name as payment_method, p.provider_reference
                 FROM orders o
                 INNER JOIN customers c ON o.customer_id = c.id
                 LEFT JOIN payments p ON o.id = p.order_id
                 WHERE o.id = ?',
                [$orderId]
            );

            if (!$order) {
                return null;
            }

            $items = $this->connection->fetchAllAssociative(
                'SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price,
                        pr.name, pr.sku, pr.image_url
                 FROM order_items oi
                 INNER JOIN products pr ON oi.product_id = pr.id
                 WHERE oi.order_id = ?',
                [$orderId]
            );

            $order['items'] = $items;

            return $order;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrdersWithCustomerAndPaymentInfo(int $limit = 50): array
    {
        try {
            return $this->connection->fetchAllAssociative(
                'SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                        c.id as customer_id, c.first_name, c.last_name, c.email,
                        pay.name as payment_method, pay.status as payment_status
                 FROM orders o
                 INNER JOIN customers c ON o.customer_id = c.id
                 LEFT JOIN payments pay ON o.id = pay.order_id
                 ORDER BY o.created_at DESC
                 LIMIT :limit',
                ['limit' => $limit]
            );
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL fetch failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getProductSalesJoin(int $categoryId): array
    {
        try {
            return $this->connection->fetchAllAssociative(
                'SELECT p.id, p.name, p.sku, p.price,
                        c.name as category_name,
                        COALESCE(SUM(oi.quantity), 0) as total_sold,
                        COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue
                 FROM products p
                 INNER JOIN categories c ON p.category_id = c.id
                 LEFT JOIN order_items oi ON p.id = oi.product_id
                 LEFT JOIN orders o ON oi.order_id = o.id AND o.status = :completed
                 WHERE p.category_id = :category_id
                 GROUP BY p.id, p.name, p.sku, p.price, c.name
                 ORDER BY total_revenue DESC',
                ['category_id' => $categoryId, 'completed' => 'completed']
            );
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL product sales query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
