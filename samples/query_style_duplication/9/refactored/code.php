<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class TransactionRepository implements TransactionRepositoryInterface
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(PDO $pdo, string $prefix = '')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        $this->pdo->beginTransaction();

        try {
            $debitSql = "UPDATE {$this->prefix}accounts
                         SET balance = balance - :amount
                         WHERE id = :from_id AND balance >= :amount";

            $stmt = $this->pdo->prepare($debitSql);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':from_id', $fromAccountId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }

            $creditSql = "UPDATE {$this->prefix}accounts
                          SET balance = balance + :amount
                          WHERE id = :to_id";

            $stmt = $this->pdo->prepare($creditSql);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':to_id', $toAccountId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Target account not found');
            }

            $logSql = "INSERT INTO {$this->prefix}transaction_logs
                       (from_account, to_account, amount, status, created_at)
                       VALUES (:from_id, :to_id, :amount, :status, NOW())";

            $stmt = $this->pdo->prepare($logSql);
            $stmt->bindValue(':from_id', $fromAccountId, PDO::PARAM_INT);
            $stmt->bindValue(':to_id', $toAccountId, PDO::PARAM_INT);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'completed');
            $stmt->execute();

            $this->pdo->commit();

            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function processOrder(array $orderData, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            $orderSql = "INSERT INTO {$this->prefix}orders
                         (customer_id, total_amount, status, created_at)
                         VALUES (:customer_id, :total_amount, :status, NOW())";

            $stmt = $this->pdo->prepare($orderSql);
            $stmt->bindValue(':customer_id', $orderData['customer_id'], PDO::PARAM_INT);
            $stmt->bindValue(':total_amount', $orderData['total_amount'], PDO::PARAM_STR);
            $stmt->bindValue(':status', $orderData['status'] ?? 'pending');
            $stmt->execute();

            $orderId = (int)$this->pdo->lastInsertId();

            foreach ($items as $item) {
                $itemSql = "INSERT INTO {$this->prefix}order_items
                            (order_id, product_id, quantity, unit_price)
                            VALUES (:order_id, :product_id, :quantity, :unit_price)";

                $stmt = $this->pdo->prepare($itemSql);
                $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                $stmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
                $stmt->bindValue(':unit_price', $item['unit_price'], PDO::PARAM_STR);
                $stmt->execute();

                $stockSql = "UPDATE {$this->prefix}products
                             SET stock_quantity = stock_quantity - :qty
                             WHERE id = :product_id AND stock_quantity >= :qty";

                $stmt = $this->pdo->prepare($stockSql);
                $stmt->bindValue(':qty', $item['quantity'], PDO::PARAM_INT);
                $stmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException("Insufficient stock for product {$item['product_id']}");
                }
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
}
