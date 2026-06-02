<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderTransactionRepository
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
            $qb = $this->connection->createQueryBuilder();
            $debit = $qb->update('accounts', 'a')
                ->set('a.balance', 'a.balance - :amount')
                ->where('a.id = :from_id')
                ->andWhere('a.balance >= :amount')
                ->setParameter('amount', $amount)
                ->setParameter('from_id', $fromAccountId)
                ->execute();

            if ($debit === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }

            $qb = $this->connection->createQueryBuilder();
            $credit = $qb->update('accounts', 'a')
                ->set('a.balance', 'a.balance + :amount')
                ->where('a.id = :to_id')
                ->setParameter('amount', $amount)
                ->setParameter('to_id', $toAccountId)
                ->execute();

            if ($credit === 0) {
                throw new RuntimeException('Target account not found');
            }

            $qb = $this->connection->createQueryBuilder();
            $qb->insert('transaction_logs')
                ->values([
                    'from_account' => ':from_id',
                    'to_account' => ':to_id',
                    'amount' => ':amount',
                    'status' => ':status',
                    'created_at' => ':created_at',
                ])
                ->setParameters([
                    'from_id' => $fromAccountId,
                    'to_id' => $toAccountId,
                    'amount' => $amount,
                    'status' => 'completed',
                    'created_at' => new \DateTimeImmutable(),
                ])
                ->execute();

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
            $qb = $this->connection->createQueryBuilder();
            $qb->insert('orders')
                ->values([
                    'customer_id' => ':customer_id',
                    'total_amount' => ':total_amount',
                    'status' => ':status',
                    'created_at' => ':created_at',
                ])
                ->setParameters([
                    'customer_id' => (int)$orderData['customer_id'],
                    'total_amount' => (float)$orderData['total_amount'],
                    'status' => $orderData['status'] ?? 'pending',
                    'created_at' => new \DateTimeImmutable(),
                ])
                ->execute();

            $orderId = (int)$this->connection->lastInsertId();

            foreach ($items as $item) {
                $qb = $this->connection->createQueryBuilder();
                $qb->insert('order_items')
                    ->values([
                        'order_id' => ':order_id',
                        'product_id' => ':product_id',
                        'quantity' => ':quantity',
                        'unit_price' => ':unit_price',
                    ])
                    ->setParameters([
                        'order_id' => $orderId,
                        'product_id' => (int)$item['product_id'],
                        'quantity' => (int)$item['quantity'],
                        'unit_price' => (float)$item['unit_price'],
                    ])
                    ->execute();

                $qb = $this->connection->createQueryBuilder();
                $updated = $qb->update('products', 'p')
                    ->set('p.stock_quantity', 'p.stock_quantity - :qty')
                    ->where('p.id = :product_id')
                    ->andWhere('p.stock_quantity >= :qty')
                    ->setParameters([
                        'qty' => (int)$item['quantity'],
                        'product_id' => (int)$item['product_id'],
                    ])
                    ->execute();

                if ($updated === 0) {
                    throw new RuntimeException("Insufficient stock for product {$item['product_id']}");
                }
            }

            $qb = $this->connection->createQueryBuilder();
            $qb->insert('payments')
                ->values([
                    'order_id' => ':order_id',
                    'amount' => ':amount',
                    'status' => ':status',
                    'created_at' => ':created_at',
                ])
                ->setParameters([
                    'order_id' => $orderId,
                    'amount' => (float)$orderData['total_amount'],
                    'status' => 'pending',
                    'created_at' => new \DateTimeImmutable(),
                ])
                ->execute();

            $this->connection->commit();

            return $orderId;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
