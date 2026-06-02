<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopOrderDetailRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);
        if ($this->connection->connect_error) {
            throw new RuntimeException('Connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT o.id, o.order_number, o.customer_id, o.total_amount, o.status,
                    o.created_at, o.shipping_address,
                    c.first_name, c.last_name, c.email,
                    p.name as payment_method, p.provider_reference
             FROM orders o
             INNER JOIN customers c ON o.customer_id = c.id
             LEFT JOIN payments p ON o.id = p.order_id
             WHERE o.id = ?'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('i', $orderId);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();

        if (!$order) {
            return null;
        }

        $itemsStmt = $this->connection->prepare(
            'SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price,
                    p.name, p.sku, p.image_url
             FROM order_items oi
             INNER JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?'
        );

        if ($itemsStmt === false) {
            throw new RuntimeException('Items prepare failed: ' . $this->connection->error);
        }

        $itemsStmt->bind_param('i', $orderId);

        if (!$itemsStmt->execute()) {
            $itemsStmt->close();
            throw new RuntimeException('Items execute failed: ' . $itemsStmt->error);
        }

        $itemsResult = $itemsStmt->get_result();
        $order['items'] = [];

        while ($item = $itemsResult->fetch_assoc()) {
            $order['items'][] = $item;
        }

        $itemsStmt->close();

        return $order;
    }

    public function getOrdersWithPagination(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->connection->prepare(
            'SELECT COUNT(*) as total FROM orders o
             INNER JOIN customers c ON o.customer_id = c.id
             WHERE o.status != ?'
        );

        $cancelled = 'cancelled';
        $countStmt->bind_param('s', $cancelled);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalCount = (int)$countResult->fetch_assoc()['total'];
        $countStmt->close();

        $stmt = $this->connection->prepare(
            'SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                    c.first_name, c.last_name, c.email,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
             FROM orders o
             INNER JOIN customers c ON o.customer_id = c.id
             WHERE o.status != ?
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('sii', $cancelled, $perPage, $offset);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $orders = [];

        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();

        return [
            'orders' => $orders,
            'total' => $totalCount,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
