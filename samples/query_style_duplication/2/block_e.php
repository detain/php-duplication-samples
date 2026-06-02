<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\DB as FacadeDB;
use RuntimeException;

final class EloquentOrderRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
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

        try {
            $now = date('Y-m-d H:i:s');

            $result = $this->db::table('orders')->insertGetId([
                'customer_id' => $customerId,
                'total_amount' => $totalAmount,
                'status' => $status,
                'notes' => $notes,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($result === false || $result === 0) {
                throw new RuntimeException('Failed to retrieve last insert ID');
            }

            return (int) $result;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent insert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function insertMany(array $orders): array
    {
        $insertIds = [];

        $this->db::table('orders')->getConnection()->transaction(function () use ($orders, &$insertIds) {
            foreach ($orders as $orderData) {
                $insertIds[] = $this->insert($orderData);
            }
        });

        return $insertIds;
    }
}
