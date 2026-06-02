<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareTransactionRepository
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

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        $this->pdo->beginTransaction();

        try {
            $debitSql = 'UPDATE accounts SET balance = balance - :amount
                         WHERE id = :from_id AND balance >= :amount';
            $debitStmt = $this->pdo->prepare($debitSql);
            $debitStmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $debitStmt->bindValue(':from_id', $fromAccountId, PDO::PARAM_INT);
            $debitStmt->execute();

            if ($debitStmt->rowCount() === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }

            $creditSql = 'UPDATE accounts SET balance = balance + :amount WHERE id = :to_id';
            $creditStmt = $this->pdo->prepare($creditSql);
            $creditStmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $creditStmt->bindValue(':to_id', $toAccountId, PDO::PARAM_INT);
            $creditStmt->execute();

            if ($creditStmt->rowCount() === 0) {
                throw new RuntimeException('Target account not found');
            }

            $logSql = 'INSERT INTO transaction_logs (from_account, to_account, amount, status, created_at)
                       VALUES (:from_id, :to_id, :amount, :status, NOW())';
            $logStmt = $this->pdo->prepare($logSql);
            $logStmt->bindValue(':from_id', $fromAccountId, PDO::PARAM_INT);
            $logStmt->bindValue(':to_id', $toAccountId, PDO::PARAM_INT);
            $logStmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $logStmt->bindValue(':status', 'completed');
            $logStmt->execute();

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
            $customerId = (int)$orderData['customer_id'];
            $totalAmount = (float)$orderData['total_amount'];
            $status = $orderData['status'] ?? 'pending';

            $orderSql = 'INSERT INTO orders (customer_id, total_amount, status, created_at)
                         VALUES (:customer_id, :total_amount, :status, NOW())';
            $orderStmt = $this->pdo->prepare($orderSql);
            $orderStmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $orderStmt->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
            $orderStmt->bindValue(':status', $status);
            $orderStmt->execute();

            $orderId = (int)$this->pdo->lastInsertId();

            if ($orderId === 0) {
                throw new RuntimeException('Failed to create order');
            }

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];

                $itemSql = 'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                             VALUES (:order_id, :product_id, :quantity, :unit_price)';
                $itemStmt = $this->pdo->prepare($itemSql);
                $itemStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $itemStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $itemStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $itemStmt->bindValue(':unit_price', $unitPrice, PDO::PARAM_STR);
                $itemStmt->execute();

                $stockSql = 'UPDATE products SET stock_quantity = stock_quantity - :qty
                             WHERE id = :product_id AND stock_quantity >= :qty';
                $stockStmt = $this->pdo->prepare($stockSql);
                $stockStmt->bindValue(':qty', $quantity, PDO::PARAM_INT);
                $stockStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stockStmt->execute();

                if ($stockStmt->rowCount() === 0) {
                    throw new RuntimeException("Insufficient stock for product {$productId}");
                }
            }

            $paymentSql = 'INSERT INTO payments (order_id, amount, status, created_at)
                           VALUES (:order_id, :amount, :status, NOW())';
            $paymentStmt = $this->pdo->prepare($paymentSql);
            $paymentStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $paymentStmt->bindValue(':amount', $totalAmount, PDO::PARAM_STR);
            $paymentStmt->bindValue(':status', 'pending');
            $paymentStmt->execute();

            $this->pdo->commit();

            return $orderId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $this->pdo->beginTransaction();
                $result = $operation($this->pdo);
                $this->pdo->commit();

                return $result;
            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw new RuntimeException(
                        "Operation failed after {$maxRetries} attempts: " . $e->getMessage(),
                        0,
                        $e
                    );
                }

                usleep(100000 * $attempts);
            }
        }

        return null;
    }
}
