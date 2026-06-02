<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentPaginationRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function getPaginatedProducts(int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        try {
            $query = $this->db::table('products as p')
                ->select('p.id', 'p.name', 'p.sku', 'p.price', 'p.stock_quantity', 'p.status',
                         'c.name as category_name', 'c.slug as category_slug')
                ->join('categories as c', 'p.category_id', '=', 'c.id')
                ->where('p.active', 1);

            if ($category !== null && $category !== '') {
                $query->where('c.slug', $category);
            }

            $total = $query->count();

            $products = $query->orderBy('p.created_at', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return [
                'items' => array_map(fn($row) => (array)$row, $products->toArray()),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPaginatedSearch(string $searchQuery, int $page = 1, int $perPage = 20): array
    {
        try {
            $query = $this->db::table('products')
                ->where('active', 1)
                ->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('description', 'LIKE', "%{$searchQuery}%");
                });

            $total = $query->count();

            $products = $query->orderBy('name', 'asc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return [
                'items' => array_map(fn($row) => (array)$row, $products->toArray()),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
                'query' => $searchQuery,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent search pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getInfiniteScroll(int $offset = 0, int $limit = 20): array
    {
        try {
            $products = $this->db::table('products')
                ->where('active', 1)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return [
                'items' => array_map(fn($row) => (array)$row, $products->toArray()),
                'offset' => $offset,
                'limit' => $limit,
                'has_more' => count($products->toArray()) === $limit,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent infinite scroll failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
