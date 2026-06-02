<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentUpsertRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function upsertProduct(array $productData): int
    {
        try {
            $result = $this->db::table('products')->updateOrInsert(
                ['sku' => $productData['sku']],
                [
                    'name' => $productData['name'],
                    'price' => $productData['price'] ?? 0,
                    'stock_quantity' => $productData['stock_quantity'] ?? 0,
                    'status' => $productData['status'] ?? 'active',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            $product = $this->db::table('products')->where('sku', $productData['sku'])->first();

            return (int)$product->id;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertUser(array $userData): int
    {
        try {
            $this->db::table('users')->updateOrInsert(
                ['email' => $userData['email']],
                [
                    'username' => $userData['username'],
                    'first_name' => $userData['first_name'] ?? '',
                    'last_name' => $userData['last_name'] ?? '',
                    'status' => $userData['status'] ?? 'active',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            $user = $this->db::table('users')->where('email', $userData['email'])->first();

            return (int)$user->id;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent user upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertMany(array $records, string $uniqueBy): int
    {
        if (empty($records)) {
            return 0;
        }

        try {
            $count = 0;

            foreach ($records as $record) {
                $this->db::table('products')->updateOrInsert(
                    [$uniqueBy => $record[$uniqueBy]],
                    $record
                );
                $count++;
            }

            return $count;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent batch upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertWithTimestamps(array $productData): int
    {
        $now = date('Y-m-d H:i:s');

        $result = $this->db::table('products')->updateOrInsert(
            ['sku' => $productData['sku']],
            array_merge($productData, [
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );

        $product = $this->db::table('products')->where('sku', $productData['sku'])->first();

        return (int)$product->id;
    }
}
