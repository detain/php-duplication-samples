<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopTransactionRepository
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

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        $this->connection->begin_transaction();

        try {
            $debitStmt = $this->connection->prepare(
                'UPDATE accounts SET balance = balance - ? WHERE id = ? AND balance >= ?'
            );
            $debitStmt->bind_param('dii', $amount, $fromAccountId, $amount);

            if (!$debitStmt->execute()) {
                throw new RuntimeException('Failed to debit account');
            }

            if ($debitStmt->affected_rows === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }
            $debitStmt->close();

            $creditStmt = $this->connection->prepare(
                'UPDATE accounts SET balance = balance + ? WHERE id = ?'
            );
            $creditStmt->bind_param('di', $amount, $toAccountId);

            if (!$creditStmt->execute()) {
                throw new RuntimeException('Failed to credit account');
            }

            if ($creditStmt->affected_rows === 0) {
                throw new RuntimeException('Target account not found');
            }
            $creditStmt->close();

            $logStmt = $this->connection->prepare(
                'INSERT INTO transaction_logs (from_account, to_account, amount, status, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $status = 'completed';
            $logStmt->bind_param('iids', $fromAccountId, $toAccountId, $amount, $status);
            $logStmt->execute();
            $logStmt->close();

            $this->connection->commit();

            return true;
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    public function processOrderWithStock(array $orderData, array $items): int
    {
        $this->connection->begin_transaction();

        try {
            $customerId = (int)$orderData['customer_id'];
            $totalAmount = (float)$orderData['total_amount'];
            $status = $orderData['status'] ?? 'pending';

            $orderStmt = $this->connection->prepare(
                'INSERT INTO orders (customer_id, total_amount, status, created_at)
                 VALUES (?, ?, ?, NOW())'
            );
            $orderStmt->bind_param('ids', $customerId, $totalAmount, $status);
            $orderStmt->execute();
            $orderId = $this->connection->insert_id;
            $orderStmt->close();

            if ($orderId === 0) {
                throw new RuntimeException('Failed to create order');
            }

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];

                $itemStmt = $this->connection->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                     VALUES (?, ?, ?, ?)'
                );
                $itemStmt->bind_param('iiid', $orderId, $productId, $quantity, $unitPrice);
                $itemStmt->execute();
                $itemStmt->close();

                $stockStmt = $this->connection->prepare(
                    'UPDATE products SET stock_quantity = stock_quantity - ?
                     WHERE id = ? AND stock_quantity >= ?'
                );
                $stockStmt->bind_param('iii', $quantity, $productId, $quantity);

                if (!$stockStmt->execute() || $stockStmt->affected_rows === 0) {
                    throw new RuntimeException("Insufficient stock for product {$productId}");
                }
                $stockStmt->close();
            }

            $paymentStmt = $this->connection->prepare(
                'INSERT INTO payments (order_id, amount, status, created_at)
                 VALUES (?, ?, ?, NOW())'
            );
            $paymentStatus = 'pending';
            $paymentStmt->bind_param('ids', $orderId, $totalAmount, $paymentStatus);
            $paymentStmt->execute();
            $paymentStmt->close();

            $this->connection->commit();

            return (int)$orderId;
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
