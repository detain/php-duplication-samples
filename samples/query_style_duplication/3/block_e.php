<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentProductRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function updatePrice(int $productId, float $newPrice): bool
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        try {
            $affected = $this->db::table('products')
                ->where('id', $productId)
                ->where('active', 1)
                ->update([
                    'price' => $newPrice,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateStock(int $productId, int $quantityChange): bool
    {
        try {
            $affected = $this->db::table('products')
                ->where('id', $productId)
                ->where('active', 1)
                ->whereRaw('stock_quantity + ? >= 0', [$quantityChange])
                ->update([
                    'stock_quantity' => $this->db::raw('stock_quantity + ' . $quantityChange),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent stock update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deactivateOutOfStock(int $threshold = 0): int
    {
        try {
            return $this->db::table('products')
                ->where('active', 1)
                ->where('stock_quantity', '<=', $threshold)
                ->update([
                    'status' => 'out_of_stock',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Exception $e) {
            throw new RuntimeException('Deactivation failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
