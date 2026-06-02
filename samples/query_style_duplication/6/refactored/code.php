<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class ReportRepository implements ReportRepositoryInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'orders')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function countByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->tableName} WHERE status = :status";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Count by status failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function sumTotal(int $customerId): float
    {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total
                FROM {$this->tableName}
                WHERE customer_id = :customer_id
                  AND status NOT IN ('cancelled', 'refunded')";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($row['total'] ?? 0.0);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Sum total failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getStats(int $customerId): array
    {
        $sql = "SELECT
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                FROM {$this->tableName}
                WHERE customer_id = :customer_id
                  AND status != 'cancelled'";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->execute();

            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_orders' => (int)($stats['total_orders'] ?? 0),
                'total_spent' => (float)($stats['total_spent'] ?? 0.0),
                'avg_order_value' => (float)($stats['avg_order_value'] ?? 0.0),
                'first_order_date' => $stats['first_order_date'] ?? null,
                'last_order_date' => $stats['last_order_date'] ?? null,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Get stats failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getPeriodSummary(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $sql = "SELECT
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as period_total,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    COALESCE(AVG(total_amount), 0) as avg_order_value
                FROM {$this->tableName}
                WHERE created_at BETWEEN :start AND :end
                  AND status = 'completed'";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':start', $start->format('Y-m-d H:i:s'));
            $stmt->bindValue(':end', $end->format('Y-m-d H:i:s'));
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Period summary failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
