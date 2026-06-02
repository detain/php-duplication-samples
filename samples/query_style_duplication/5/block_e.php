<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentArticleRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        try {
            $results = $this->db::table('articles')
                ->select('id', 'title', 'slug', 'excerpt', 'content', 'author_id', 'published_at', 'status')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%")
                      ->orWhere('tags', 'LIKE', "%{$query}%");
                })
                ->where('status', 'published')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();

            return array_map(fn($row) => (array)$row, $results);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        try {
            $results = $this->db::table('articles')
                ->select('id', 'title', 'slug', 'excerpt', 'author_id', 'published_at')
                ->where('tags', 'LIKE', "%{$tag}%")
                ->where('status', 'published')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();

            return array_map(fn($row) => (array)$row, $results);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent tag search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchByAuthor(int $authorId, ?string $query = null): array
    {
        try {
            $builder = $this->db::table('articles')
                ->select('id', 'title', 'slug', 'excerpt', 'published_at', 'status')
                ->where('author_id', $authorId);

            if ($query !== null && $query !== '') {
                $builder->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                });
            }

            $results = $builder->orderBy('published_at', 'desc')
                ->limit(50)
                ->get();

            return array_map(fn($row) => (array)$row, $results);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent author search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchWithFilters(array $filters): array
    {
        try {
            $builder = $this->db::table('articles')
                ->select('*');

            if (!empty($filters['query'])) {
                $builder->where(function ($q) use ($filters) {
                    $q->where('title', 'LIKE', "%{$filters['query']}%")
                      ->orWhere('content', 'LIKE', "%{$filters['query']}%");
                });
            }

            if (!empty($filters['author_id'])) {
                $builder->where('author_id', $filters['author_id']);
            }

            if (!empty($filters['tag'])) {
                $builder->where('tags', 'LIKE', "%{$filters['tag']}%");
            }

            if (!empty($filters['status'])) {
                $builder->where('status', $filters['status']);
            }

            $limit = $filters['limit'] ?? 20;
            $offset = $filters['offset'] ?? 0;

            $results = $builder->orderBy('published_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();

            return array_map(fn($row) => (array)$row, $results);
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent filtered search failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
