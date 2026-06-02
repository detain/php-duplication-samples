<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentTransactionRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function transferFunds(int $fromAccountId, int $toAccountId, float $amount): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive');
        }

        return $this->db::getDatabaseManager()->transaction(function () use ($fromAccountId, $toAccountId, $amount) {
            $debited = $this->db::table('accounts')
                ->where('id', $fromAccountId)
                ->where('balance', '>=', $amount)
                ->decrement('balance', $amount);

            if ($debited === 0) {
                throw new RuntimeException('Insufficient funds or account not found');
            }

            $credited = $this->db::table('accounts')
                ->where('id', $toAccountId)
                ->increment('balance', $amount);

            if ($credited === 0) {
                throw new RuntimeException('Target account not found');
            }

            $this->db::table('transaction_logs')->insert([
                'from_account' => $fromAccountId,
                'to_account' => $toAccountId,
                'amount' => $amount,
                'status' => 'completed',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        });
    }

    public function processOrder(array $orderData, array $items): int
    {
        return $this->db::getDatabaseManager()->transaction(function () use ($orderData, $items) {
            $orderId = $this->db::table('orders')->insertGetId([
                'customer_id' => $orderData['customer_id'],
                'total_amount' => $orderData['total_amount'],
                'status' => $orderData['status'] ?? 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($items as $item) {
                $this->db::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                $updated = $this->db::table('products')
                    ->where('id', $item['product_id'])
                    ->where('stock_quantity', '>=', $item['quantity'])
                    ->decrement('stock_quantity', $item['quantity']);

                if ($updated === 0) {
                    throw new RuntimeException("Insufficient stock for product {$item['product_id']}");
                }
            }

            $this->db::table('payments')->insert([
                'order_id' => $orderId,
                'amount' => $orderData['total_amount'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return (int)$orderId;
        });
    }

    public function executeWithAutomaticRollback(callable $operations): array
    {
        $results = [];

        $this->db::getDatabaseManager()->transaction(function () use ($operations, &$results) {
            $results = $operations();
        });

        return $results;
    }
}
