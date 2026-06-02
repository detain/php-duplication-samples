<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class PaginationRepository implements PaginationRepositoryInterface
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(PDO $pdo, string $prefix = '')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getPaginatedProducts(int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total
                     FROM {$this->prefix}products p
                     INNER JOIN {$this->prefix}categories c ON p.category_id = c.id
                     WHERE p.active = 1";

        $sql = "SELECT p.id, p.name, p.sku, p.price, p.stock_quantity, p.status,
                       c.name as category_name, c.slug as category_slug
                FROM {$this->prefix}products p
                INNER JOIN {$this->prefix}categories c ON p.category_id = c.id
                WHERE p.active = 1";

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

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'items' => $products,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Pagination query failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getPaginatedSearch(string $query, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $searchPattern = "%{$query}%";

        $countSql = "SELECT COUNT(*) as total FROM {$this->prefix}products
                     WHERE active = 1 AND (name LIKE :q1 OR description LIKE :q2)";

        $sql = "SELECT id, name, sku, price, stock_quantity, status
                FROM {$this->prefix}products
                WHERE active = 1 AND (name LIKE :q1 OR description LIKE :q2)
                ORDER BY name ASC LIMIT :limit OFFSET :offset";

        try {
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->bindValue(':q1', $searchPattern);
            $countStmt->bindValue(':q2', $searchPattern);
            $countStmt->execute();
            $total = (int)$countStmt->fetch()['total'];

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q1', $searchPattern);
            $stmt->bindValue(':q2', $searchPattern);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'items' => $products,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
                'query' => $query,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Search pagination failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
