<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralReportRepository
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

    public function countOrdersByStatus(string $status): int
    {
        $status = mysqli_real_escape_string($this->connection, $status);

        $sql = "SELECT COUNT(*) as cnt FROM orders WHERE status = '{$status}'";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Count query failed: ' . mysqli_error($this->connection));
        }

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return (int)($row['cnt'] ?? 0);
    }

    public function sumOrderTotal(int $customerId): float
    {
        $customerId = (int)$customerId;

        $sql = "SELECT SUM(total_amount) as total FROM orders
                WHERE customer_id = {$customerId}
                  AND status NOT IN ('cancelled', 'refunded')";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Sum query failed: ' . mysqli_error($this->connection));
        }

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return (float)($row['total'] ?? 0.0);
    }

    public function getOrderStats(int $customerId): array
    {
        $customerId = (int)$customerId;

        $sql = "SELECT
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_spent,
                    AVG(total_amount) as avg_order_value,
                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                FROM orders
                WHERE customer_id = {$customerId}
                  AND status != 'cancelled'";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Stats query failed: ' . mysqli_error($this->connection));
        }

        $stats = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return [
            'total_orders' => (int)($stats['total_orders'] ?? 0),
            'total_spent' => (float)($stats['total_spent'] ?? 0.0),
            'avg_order_value' => (float)($stats['avg_order_value'] ?? 0.0),
            'first_order_date' => $stats['first_order_date'] ?? null,
            'last_order_date' => $stats['last_order_date'] ?? null,
        ];
    }

    public function getDailySales(\DateTimeInterface $date): array
    {
        $dateStr = $date->format('Y-m-d');

        $sql = "SELECT
                    COUNT(*) as order_count,
                    SUM(total_amount) as daily_total,
                    SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_total,
                    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_total
                FROM orders
                WHERE DATE(created_at) = '{$dateStr}'";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Daily sales query failed: ' . mysqli_error($this->connection));
        }

        return mysqli_fetch_assoc($result) ?: [];
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
