<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPreparePaginationRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function getPaginatedProducts(int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $countSql = 'SELECT COUNT(*) as total
                     FROM products p
                     INNER JOIN categories c ON p.category_id = c.id
                     WHERE p.active = 1';

        $sql = 'SELECT p.id, p.name, p.sku, p.price, p.stock_quantity, p.status,
                       c.name as category_name, c.slug as category_slug
                FROM products p
                INNER JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1';

        $params = [];

        if ($category !== null && $category !== '') {
            $countSql .= ' AND c.slug = :category';
            $sql .= ' AND c.slug = :category';
            $params['category'] = $category;
        }

        try {
            $countStmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":{$key}", $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetch()['total'];

            $sql .= ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset';
            $params['limit'] = $perPage;
            $params['offset'] = $offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->execute();

            $products = $stmt->fetchAll();

            return [
                'items' => $products,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('Pagination query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPaginatedSearch(string $query, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $searchPattern = "%{$query}%";

        $countSql = 'SELECT COUNT(*) as total
                     FROM products
                     WHERE active = 1
                       AND (name LIKE :query OR description LIKE :query2)';

        $sql = 'SELECT id, name, sku, price, stock_quantity, status
                FROM products
                WHERE active = 1
                  AND (name LIKE :query OR description LIKE :query2)
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset';

        try {
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->bindValue(':query', $searchPattern);
            $countStmt->bindValue(':query2', $searchPattern);
            $countStmt->execute();
            $total = (int)$countStmt->fetch()['total'];

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':query', $searchPattern);
            $stmt->bindValue(':query2', $searchPattern);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $products = $stmt->fetchAll();

            return [
                'items' => $products,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
                'query' => $query,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('Search pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getScrollPagination(int $lastId = 0, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $sql = 'SELECT id, name, sku, price, created_at
                FROM products
                WHERE active = 1 AND id > :last_id
                ORDER BY id ASC
                LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $items = $stmt->fetchAll();

            return [
                'items' => $items,
                'next_cursor' => !empty($items) ? (int)end($items)['id'] : $lastId,
                'has_more' => count($items) === $limit,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('Scroll pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
