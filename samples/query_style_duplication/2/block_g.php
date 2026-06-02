<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperOrderRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function insert(array $orderData): int
    {
        $customerId = (int)($orderData['customer_id'] ?? 0);
        $totalAmount = (float)($orderData['total_amount'] ?? 0.0);
        $status = $orderData['status'] ?? 'pending';
        $notes = $orderData['notes'] ?? '';

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Valid customer_id is required');
        }

        $tableName = $this->tablePrefix . 'orders';

        $sql = <<<SQL
            INSERT INTO {$tableName} (customer_id, total_amount, status, notes, created_at)
            VALUES (:customer_id, :total_amount, :status, :notes, NOW())
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();

            $insertId = $this->pdo->lastInsertId();

            if ($insertId === false || $insertId === '0') {
                throw new RuntimeException("Failed to retrieve last insert ID from {$tableName}");
            }

            return (int) $insertId;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to insert order into {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function insertOrUpdate(array $orderData): int
    {
        $customerId = (int)($orderData['customer_id'] ?? 0);
        $totalAmount = (float)($orderData['total_amount'] ?? 0.0);
        $status = $orderData['status'] ?? 'pending';

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Valid customer_id is required');
        }

        $tableName = $this->tablePrefix . 'orders';

        $sql = <<<SQL
            INSERT INTO {$tableName} (customer_id, total_amount, status, created_at, updated_at)
            VALUES (:customer_id, :total_amount, :status, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                total_amount = VALUES(total_amount),
                status = VALUES(status),
                updated_at = NOW()
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->execute();

            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to upsert order into {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
