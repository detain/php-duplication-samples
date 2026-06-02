<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareReportRepository
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

    public function countOrdersByStatus(string $status): int
    {
        $sql = 'SELECT COUNT(*) as cnt FROM orders WHERE status = :status';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->execute();

            $row = $stmt->fetch();

            return (int)($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException('Count query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sumOrderTotal(int $customerId): float
    {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) as total
                 FROM orders
                 WHERE customer_id = :customer_id
                   AND status NOT IN (:cancelled, :refunded)';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':cancelled', 'cancelled');
            $stmt->bindValue(':refunded', 'refunded');
            $stmt->execute();

            $row = $stmt->fetch();

            return (float)($row['total'] ?? 0.0);
        } catch (PDOException $e) {
            throw new RuntimeException('Sum query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrderStats(int $customerId): array
    {
        $sql = 'SELECT
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                FROM orders
                WHERE customer_id = :customer_id
                  AND status != :cancelled';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':cancelled', 'cancelled');
            $stmt->execute();

            $stats = $stmt->fetch();

            return [
                'total_orders' => (int)($stats['total_orders'] ?? 0),
                'total_spent' => (float)($stats['total_spent'] ?? 0.0),
                'avg_order_value' => (float)($stats['avg_order_value'] ?? 0.0),
                'first_order_date' => $stats['first_order_date'] ?? null,
                'last_order_date' => $stats['last_order_date'] ?? null,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('Stats query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getMonthlyRevenue(int $year, int $month): array
    {
        $sql = 'SELECT
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as monthly_total,
                    COALESCE(AVG(total_amount), 0) as avg_order_value
                FROM orders
                WHERE YEAR(created_at) = :year
                  AND MONTH(created_at) = :month
                  AND status = :completed';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->bindValue(':month', $month, PDO::PARAM_INT);
            $stmt->bindValue(':completed', 'completed');
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new RuntimeException('Monthly revenue query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getInventoryStats(): array
    {
        $sql = 'SELECT
                    COUNT(*) as total_products,
                    COALESCE(SUM(stock_quantity), 0) as total_stock,
                    COALESCE(AVG(price), 0) as avg_price,
                    COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock_count,
                    COUNT(CASE WHEN stock_quantity < 10 THEN 1 END) as low_stock_count
                FROM products
                WHERE active = 1';

        try {
            $stmt = $this->pdo->query($sql);

            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new RuntimeException('Inventory stats query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
