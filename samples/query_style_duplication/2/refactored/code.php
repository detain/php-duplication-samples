<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class OrderRepository implements OrderRepositoryInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'orders')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function create(array $orderData): int
    {
        $customerId = $this->validateCustomerId($orderData['customer_id'] ?? 0);
        $totalAmount = $this->validateAmount($orderData['total_amount'] ?? 0.0);
        $status = $orderData['status'] ?? 'pending';
        $notes = $orderData['notes'] ?? '';

        $sql = "INSERT INTO {$this->tableName} (customer_id, total_amount, status, notes, created_at)
                 VALUES (:customer_id, :total_amount, :status, :notes, NOW())";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();

            $insertId = $this->pdo->lastInsertId();

            if ($insertId === false || $insertId === '0') {
                throw new RuntimeException('Failed to retrieve last insert ID');
            }

            return (int) $insertId;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to create order: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function createWithItems(array $orderData, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            $orderId = $this->create($orderData);

            $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                        VALUES (:order_id, :product_id, :quantity, :unit_price)";
            $stmt = $this->pdo->prepare($itemSql);

            foreach ($items as $item) {
                $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                $stmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
                $stmt->bindValue(':unit_price', $item['unit_price'], PDO::PARAM_STR);
                $stmt->execute();
            }

            $this->pdo->commit();

            return $orderId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function validateCustomerId(int $id): int
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Valid customer_id is required');
        }
        return $id;
    }

    private function validateAmount(float $amount): float
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        return $amount;
    }
}
