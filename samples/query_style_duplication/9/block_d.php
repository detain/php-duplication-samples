<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalTransactionRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        $this->connection->beginTransaction();

        try {
            $debit = $this->connection->executeStatement(
                'UPDATE accounts SET balance = balance - ? WHERE id = ? AND balance >= ?',
                [$amount, $fromAccountId, $amount]
            );

            if ($debit === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }

            $credit = $this->connection->executeStatement(
                'UPDATE accounts SET balance = balance + ? WHERE id = ?',
                [$amount, $toAccountId]
            );

            if ($credit === 0) {
                throw new RuntimeException('Target account not found');
            }

            $this->connection->insert('transaction_logs', [
                'from_account' => $fromAccountId,
                'to_account' => $toAccountId,
                'amount' => $amount,
                'status' => 'completed',
                'created_at' => new \DateTimeImmutable(),
            ]);

            $this->connection->commit();

            return true;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function processOrder(array $orderData, array $items): int
    {
        $this->connection->beginTransaction();

        try {
            $this->connection->insert('orders', [
                'customer_id' => (int)$orderData['customer_id'],
                'total_amount' => (float)$orderData['total_amount'],
                'status' => $orderData['status'] ?? 'pending',
                'created_at' => new \DateTimeImmutable(),
            ]);

            $orderId = (int)$this->connection->lastInsertId();

            foreach ($items as $item) {
                $this->connection->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => (int)$item['product_id'],
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                ]);

                $updated = $this->connection->executeStatement(
                    'UPDATE products SET stock_quantity = stock_quantity - ?
                     WHERE id = ? AND stock_quantity >= ?',
                    [(int)$item['quantity'], (int)$item['product_id'], (int)$item['quantity']]
                );

                if ($updated === 0) {
                    throw new RuntimeException("Insufficient stock for product {$item['product_id']}");
                }
            }

            $this->connection->insert('payments', [
                'order_id' => $orderId,
                'amount' => (float)$orderData['total_amount'],
                'status' => 'pending',
                'created_at' => new \DateTimeImmutable(),
            ]);

            $this->connection->commit();

            return $orderId;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function batchProcessOrders(array $orders): array
    {
        $this->connection->beginTransaction();

        $orderIds = [];

        try {
            foreach ($orders as $orderData) {
                $this->connection->insert('orders', [
                    'customer_id' => (int)$orderData['customer_id'],
                    'total_amount' => (float)$orderData['total_amount'],
                    'status' => 'pending',
                    'created_at' => new \DateTimeImmutable(),
                ]);

                $orderIds[] = (int)$this->connection->lastInsertId();
            }

            $this->connection->commit();

            return $orderIds;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
