<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareOrderDetailRepository
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

    public function getOrderWithDetails(int $orderId): ?array
    {
        $sql = 'SELECT o.id, o.order_number, o.customer_id, o.total_amount, o.status,
                       o.created_at, o.shipping_address,
                       c.first_name, c.last_name, c.email,
                       p.name as payment_method, p.provider_reference
                FROM orders o
                INNER JOIN customers c ON o.customer_id = c.id
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.id = :order_id';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            $order = $stmt->fetch();

            if (!$order) {
                return null;
            }

            $itemsSql = 'SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price,
                                p.name, p.sku, p.image_url
                         FROM order_items oi
                         INNER JOIN products p ON oi.product_id = p.id
                         WHERE oi.order_id = :order_id';

            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $itemsStmt->execute();

            $order['items'] = $itemsStmt->fetchAll();

            return $order;
        } catch (PDOException $e) {
            throw new RuntimeException('Query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCustomerOrderHistory(int $customerId, int $limit = 20): array
    {
        $sql = 'SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                       c.first_name, c.last_name,
                       COUNT(oi.id) as item_count,
                       pay.name as last_payment_method
                FROM orders o
                INNER JOIN customers c ON o.customer_id = c.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN payments pay ON o.id = pay.order_id
                WHERE o.customer_id = :customer_id
                GROUP BY o.id, o.order_number, o.total_amount, o.status, o.created_at,
                         c.first_name, c.last_name, pay.name
                ORDER BY o.created_at DESC
                LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new RuntimeException('History query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getRecentOrdersWithCustomerInfo(int $limit = 50): array
    {
        $sql = 'SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                       c.id as customer_id, c.first_name, c.last_name, c.email,
                       c.phone, c.loyalty_tier
                FROM orders o
                INNER JOIN customers c ON o.customer_id = c.id
                ORDER BY o.created_at DESC
                LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new RuntimeException('Recent orders query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
