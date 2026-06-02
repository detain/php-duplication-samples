<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperPaginationRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function getPaginatedProducts(int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $tableName = $this->tablePrefix . 'products';

        if ($category !== null && $category !== '') {
            $countSql = <<<SQL
                SELECT COUNT(*) as total
                FROM {$tableName} p
                INNER JOIN {$this->tablePrefix}categories c ON p.category_id = c.id
                WHERE p.active = 1 AND c.slug = :category
SQL;
            $sql = <<<SQL
                SELECT p.id, p.name, p.sku, p.price, p.stock_quantity, p.status,
                       c.name as category_name, c.slug as category_slug
                FROM {$tableName} p
                INNER JOIN {$this->tablePrefix}categories c ON p.category_id = c.id
                WHERE p.active = 1 AND c.slug = :category
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
SQL;
            $countParams = ['category' => $category];
            $params = ['category' => $category, 'limit' => $perPage, 'offset' => $offset];
        } else {
            $countSql = "SELECT COUNT(*) as total FROM {$tableName} WHERE active = 1";
            $sql = <<<SQL
                SELECT p.id, p.name, p.sku, p.price, p.stock_quantity, p.status
                FROM {$tableName} p
                WHERE p.active = 1
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
SQL;
            $countParams = [];
            $params = ['limit' => $perPage, 'offset' => $offset];
        }

        try {
            $countStmt = $this->pdo->prepare($countSql);
            foreach ($countParams as $key => $value) {
                $countStmt->bindValue(":{$key}", $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetch()['total'];

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === 'limit' || $key === 'offset') {
                    $stmt->bindValue(":{$key}", $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":{$key}", $value);
                }
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
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Pagination query failed on {$tableName}: " . $e->getMessage(),
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
        $tableName = $this->tablePrefix . 'products';

        $countSql = "SELECT COUNT(*) as total FROM {$tableName}
                     WHERE active = 1 AND (name LIKE :q1 OR description LIKE :q2)";
        $sql = "SELECT id, name, sku, price, stock_quantity, status
                FROM {$tableName}
                WHERE active = 1 AND (name LIKE :q1 OR description LIKE :q2)
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset";

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
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Search pagination failed on {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
