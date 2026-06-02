<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentReportRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function countOrdersByStatus(string $status): int
    {
        try {
            return $this->db::table('orders')
                ->where('status', $status)
                ->count();
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent count failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function sumOrderTotal(int $customerId): float
    {
        try {
            $result = $this->db::table('orders')
                ->where('customer_id', $customerId)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total')
                ->first();

            return (float)($result->total ?? 0.0);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent sum failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getOrderStats(int $customerId): array
    {
        try {
            $result = $this->db::table('orders')
                ->where('customer_id', $customerId)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                ')
                ->first();

            return [
                'total_orders' => (int)($result->total_orders ?? 0),
                'total_spent' => (float)($result->total_spent ?? 0.0),
                'avg_order_value' => (float)($result->avg_order_value ?? 0.0),
                'first_order_date' => $result->first_order_date ?? null,
                'last_order_date' => $result->last_order_date ?? null,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent stats failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getSalesByMonth(int $year): array
    {
        try {
            $results = $this->db::table('orders')
                ->whereYear('created_at', $year)
                ->where('status', 'completed')
                ->selectRaw('
                    MONTH(created_at) as month,
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as monthly_total
                ')
                ->groupByRaw('MONTH(created_at)')
                ->orderByRaw('MONTH(created_at)')
                ->get();

            return array_map(fn($row) => (array)$row, $results);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent monthly sales failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getTopSellingProducts(int $limit = 10): array
    {
        try {
            $results = $this->db::table('order_items as oi')
                ->join('orders as o', 'oi.order_id', '=', 'o.id')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('o.status', 'completed')
                ->selectRaw('
                    p.id,
                    p.name,
                    COUNT(oi.id) as times_sold,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.unit_price) as total_revenue
                ')
                ->groupBy('p.id', 'p.name')
                ->orderByRaw('total_revenue DESC')
                ->limit($limit)
                ->get();

            return array_map(fn($row) => (array)$row, $results);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent top products failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
