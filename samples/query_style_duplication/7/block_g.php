<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperOrderDetailRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        $tablePrefix = $this->tablePrefix;

        $sql = <<<SQL
            SELECT o.id, o.order_number, o.customer_id, o.total_amount, o.status,
                   o.created_at, o.shipping_address,
                   c.first_name, c.last_name, c.email,
                   p.name as payment_method, p.provider_reference
            FROM {$tablePrefix}orders o
            INNER JOIN {$tablePrefix}customers c ON o.customer_id = c.id
            LEFT JOIN {$tablePrefix}payments p ON o.id = p.order_id
            WHERE o.id = :order_id
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return null;
            }

            $itemsSql = "SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price,
                                pr.name, pr.sku, pr.image_url
                         FROM {$tablePrefix}order_items oi
                         INNER JOIN {$tablePrefix}products pr ON oi.product_id = pr.id
                         WHERE oi.order_id = :order_id";

            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $itemsStmt->execute();

            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return $order;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to get order details: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getCustomerOrderHistory(int $customerId, int $limit = 20): array
    {
        $tablePrefix = $this->tablePrefix;

        $sql = <<<SQL
            SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                   c.first_name, c.last_name,
                   COUNT(oi.id) as item_count,
                   pay.name as last_payment_method
            FROM {$tablePrefix}orders o
            INNER JOIN {$tablePrefix}customers c ON o.customer_id = c.id
            LEFT JOIN {$tablePrefix}order_items oi ON o.id = oi.order_id
            LEFT JOIN {$tablePrefix}payments pay ON o.id = pay.order_id
            WHERE o.customer_id = :customer_id
            GROUP BY o.id, o.order_number, o.total_amount, o.status, o.created_at,
                     c.first_name, c.last_name, pay.name
            ORDER BY o.created_at DESC
            LIMIT :limit
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to get customer order history: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getOrderAnalytics(int $days = 30): array
    {
        $tablePrefix = $this->tablePrefix;
        $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $sql = <<<SQL
            SELECT
                DATE(o.created_at) as order_date,
                COUNT(DISTINCT o.customer_id) as unique_customers,
                COUNT(*) as order_count,
                SUM(o.total_amount) as daily_total,
                AVG(o.total_amount) as avg_order_value,
                c.name as top_category
            FROM {$tablePrefix}orders o
            LEFT JOIN {$tablePrefix}order_items oi ON o.id = oi.order_id
            LEFT JOIN {$tablePrefix}products p ON oi.product_id = p.id
            LEFT JOIN {$tablePrefix}categories c ON p.category_id = c.id
            WHERE o.created_at >= :date_limit
              AND o.status = 'completed'
            GROUP BY DATE(o.created_at), c.name
            ORDER BY order_date DESC, order_count DESC
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':date_limit', $dateLimit);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to get order analytics: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
