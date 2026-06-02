<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentOrderDetailRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        try {
            $order = $this->db::table('orders as o')
                ->select(
                    'o.id', 'o.order_number', 'o.customer_id', 'o.total_amount', 'o.status',
                    'o.created_at', 'o.shipping_address',
                    'c.first_name', 'c.last_name', 'c.email',
                    'p.name as payment_method', 'p.provider_reference'
                )
                ->join('customers as c', 'o.customer_id', '=', 'c.id')
                ->leftJoin('payments as p', 'o.id', '=', 'p.order_id')
                ->where('o.id', $orderId)
                ->first();

            if (!$order) {
                return null;
            }

            $items = $this->db::table('order_items as oi')
                ->select('oi.id', 'oi.product_id', 'oi.quantity', 'oi.unit_price', 'pr.name', 'pr.sku')
                ->join('products as pr', 'oi.product_id', '=', 'pr.id')
                ->where('oi.order_id', $orderId)
                ->get();

            $order = (array)$order;
            $order['items'] = array_map(fn($item) => (array)$item, $items->toArray());

            return $order;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCustomerOrderHistory(int $customerId, int $limit = 20): array
    {
        try {
            $results = $this->db::table('orders as o')
                ->select(
                    'o.id', 'o.order_number', 'o.total_amount', 'o.status', 'o.created_at',
                    'c.first_name', 'c.last_name',
                    $this->db::raw('COUNT(oi.id) as item_count')
                )
                ->join('customers as c', 'o.customer_id', '=', 'c.id')
                ->leftJoin('order_items as oi', 'o.id', '=', 'oi.order_id')
                ->where('o.customer_id', $customerId)
                ->groupBy('o.id', 'o.order_number', 'o.total_amount', 'o.status', 'o.created_at', 'c.first_name', 'c.last_name')
                ->orderBy('o.created_at', 'desc')
                ->limit($limit)
                ->get();

            return array_map(fn($row) => (array)$row, $results->toArray());
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent history query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrdersWithRelations(int $limit = 100): array
    {
        try {
            $results = $this->db::table('orders as o')
                ->select(
                    'o.id', 'o.order_number', 'o.total_amount', 'o.status', 'o.created_at',
                    'c.id as customer_id', 'c.first_name', 'c.last_name', 'c.email',
                    'pay.name as payment_method'
                )
                ->join('customers as c', 'o.customer_id', '=', 'c.id')
                ->leftJoin('payments as pay', 'o.id', '=', 'pay.order_id')
                ->orderBy('o.created_at', 'desc')
                ->limit($limit)
                ->get();

            return array_map(fn($row) => (array)$row, $results->toArray());
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent relations query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
