<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralOrderDetailRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = mysqli_connect($host, $username, $password, $database);
        if ($this->connection === false) {
            throw new RuntimeException('Connection failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        $orderId = (int)$orderId;

        $sql = "SELECT o.id, o.order_number, o.customer_id, o.total_amount, o.status,
                       o.created_at, o.shipping_address,
                       c.first_name, c.last_name, c.email,
                       p.name as payment_method, p.provider_reference
                FROM orders o
                INNER JOIN customers c ON o.customer_id = c.id
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.id = {$orderId}";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Query failed: ' . mysqli_error($this->connection));
        }

        $order = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        if (!$order) {
            return null;
        }

        $itemsSql = "SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price,
                            p.name, p.sku, p.image_url
                     FROM order_items oi
                     INNER JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = {$orderId}";

        $itemsResult = mysqli_query($this->connection, $itemsSql);

        if ($itemsResult === false) {
            throw new RuntimeException('Items query failed: ' . mysqli_error($this->connection));
        }

        $order['items'] = [];
        while ($item = mysqli_fetch_assoc($itemsResult)) {
            $order['items'][] = $item;
        }
        mysqli_free_result($itemsResult);

        return $order;
    }

    public function getCustomerOrderHistory(int $customerId, int $limit = 20): array
    {
        $customerId = (int)$customerId;
        $limit = (int)$limit;

        $sql = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                       c.first_name, c.last_name,
                       COUNT(oi.id) as item_count,
                       p.name as last_payment_method
                FROM orders o
                INNER JOIN customers c ON o.customer_id = c.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.customer_id = {$customerId}
                GROUP BY o.id, o.order_number, o.total_amount, o.status, o.created_at,
                         c.first_name, c.last_name, p.name
                ORDER BY o.created_at DESC
                LIMIT {$limit}";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('History query failed: ' . mysqli_error($this->connection));
        }

        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
        mysqli_free_result($result);

        return $orders;
    }

    public function getProductSalesWithCategories(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $ids = implode(',', array_map('intval', $productIds));

        $sql = "SELECT p.id, p.name, p.sku, p.price,
                       c.name as category_name, c.slug as category_slug,
                       COALESCE(SUM(oi.quantity), 0) as total_sold,
                       COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_revenue
                FROM products p
                INNER JOIN categories c ON p.category_id = c.id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                WHERE p.id IN ({$ids})
                GROUP BY p.id, p.name, p.sku, p.price, c.name, c.slug
                ORDER BY total_revenue DESC";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Product sales query failed: ' . mysqli_error($this->connection));
        }

        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_free_result($result);

        return $products;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
