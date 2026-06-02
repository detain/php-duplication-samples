<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopReportRepository
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

    public function countOrdersByStatus(string $status): int
    {
        $stmt = $this->connection->prepare('SELECT COUNT(*) as cnt FROM orders WHERE status = ?');

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('s', $status);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)($row['cnt'] ?? 0);
    }

    public function sumOrderTotal(int $customerId): float
    {
        $stmt = $this->connection->prepare(
            'SELECT SUM(total_amount) as total FROM orders
             WHERE customer_id = ? AND status NOT IN (?, ?)'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $cancelled = 'cancelled';
        $refunded = 'refunded';
        $stmt->bind_param('iss', $customerId, $cancelled, $refunded);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (float)($row['total'] ?? 0.0);
    }

    public function getOrderStats(int $customerId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_spent,
                COALESCE(AVG(total_amount), 0) as avg_order_value,
                MIN(created_at) as first_order_date,
                MAX(created_at) as last_order_date
             FROM orders
             WHERE customer_id = ? AND status != ?'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $cancelled = 'cancelled';
        $stmt->bind_param('is', $customerId, $cancelled);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();

        return [
            'total_orders' => (int)($stats['total_orders'] ?? 0),
            'total_spent' => (float)($stats['total_spent'] ?? 0.0),
            'avg_order_value' => (float)($stats['avg_order_value'] ?? 0.0),
            'first_order_date' => $stats['first_order_date'] ?? null,
            'last_order_date' => $stats['last_order_date'] ?? null,
        ];
    }

    public function getProductSalesReport(int $categoryId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT
                p.id,
                p.name,
                COUNT(oi.id) as times_ordered,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.quantity * oi.unit_price) as total_revenue
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id AND o.status NOT IN (?, ?)
             WHERE p.category_id = ?
             GROUP BY p.id, p.name
             ORDER BY total_revenue DESC'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $cancelled = 'cancelled';
        $refunded = 'refunded';
        $stmt->bind_param('ssi', $cancelled, $refunded, $categoryId);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $report = [];

        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }

        $stmt->close();

        return $report;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
