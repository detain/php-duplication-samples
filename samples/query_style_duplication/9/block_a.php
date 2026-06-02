<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralTransactionRepository
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

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        $fromAccountId = (int)$fromAccountId;
        $toAccountId = (int)$toAccountId;

        mysqli_autocommit($this->connection, false);

        try {
            $debitSql = "UPDATE accounts SET balance = balance - {$amount} WHERE id = {$fromAccountId} AND balance >= {$amount}";
            $debitResult = mysqli_query($this->connection, $debitSql);

            if ($debitResult === false || mysqli_affected_rows($this->connection) === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }

            $creditSql = "UPDATE accounts SET balance = balance + {$amount} WHERE id = {$toAccountId}";
            $creditResult = mysqli_query($this->connection, $creditSql);

            if ($creditResult === false || mysqli_affected_rows($this->connection) === 0) {
                throw new RuntimeException('Target account not found');
            }

            $logSql = "INSERT INTO transaction_logs (from_account, to_account, amount, status, created_at)
                       VALUES ({$fromAccountId}, {$toAccountId}, {$amount}, 'completed', NOW())";
            mysqli_query($this->connection, $logSql);

            mysqli_commit($this->connection);
            mysqli_autocommit($this->connection, true);

            return true;
        } catch (\Exception $e) {
            mysqli_rollback($this->connection);
            mysqli_autocommit($this->connection, true);
            throw $e;
        }
    }

    public function processOrder(array $orderData, array $items): int
    {
        mysqli_autocommit($this->connection, false);

        try {
            $customerId = (int)$orderData['customer_id'];
            $totalAmount = (float)$orderData['total_amount'];
            $status = mysqli_real_escape_string($this->connection, $orderData['status'] ?? 'pending');

            $orderSql = "INSERT INTO orders (customer_id, total_amount, status, created_at)
                         VALUES ({$customerId}, {$totalAmount}, '{$status}', NOW())";
            mysqli_query($this->connection, $orderSql);

            $orderId = mysqli_insert_id($this->connection);

            if ($orderId === 0) {
                throw new RuntimeException('Failed to create order');
            }

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];

                $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                            VALUES ({$orderId}, {$productId}, {$quantity}, {$unitPrice})";
                mysqli_query($this->connection, $itemSql);

                $stockSql = "UPDATE products SET stock_quantity = stock_quantity - {$quantity}
                             WHERE id = {$productId} AND stock_quantity >= {$quantity}";
                mysqli_query($this->connection, $stockSql);

                if (mysqli_affected_rows($this->connection) === 0) {
                    throw new RuntimeException("Insufficient stock for product {$productId}");
                }
            }

            $paymentSql = "INSERT INTO payments (order_id, amount, status, created_at)
                           VALUES ({$orderId}, {$totalAmount}, 'pending', NOW())";
            mysqli_query($this->connection, $paymentSql);

            mysqli_commit($this->connection);
            mysqli_autocommit($this->connection, true);

            return (int)$orderId;
        } catch (\Exception $e) {
            mysqli_rollback($this->connection);
            mysqli_autocommit($this->connection, true);
            throw $e;
        }
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
