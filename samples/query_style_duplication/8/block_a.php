<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralPaginationRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = mysqli_connect($host, $username, $password, $database);
        if ($this->connection === false) {
            throw new RuntimeException('Connection failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function getPaginatedProducts(int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        $whereClause = "WHERE p.active = 1";
        $countWhereClause = "WHERE p.active = 1";

        if ($category !== null && $category !== '') {
            $categoryEscaped = mysqli_real_escape_string($this->connection, $category);
            $whereClause .= " AND c.slug = '{$categoryEscaped}'";
            $countWhereClause .= " AND c.slug = '{$categoryEscaped}'";
        }

        $countSql = "SELECT COUNT(*) as total
                     FROM products p
                     INNER JOIN categories c ON p.category_id = c.id
                     {$countWhereClause}";

        $countResult = mysqli_query($this->connection, $countSql);
        if ($countResult === false) {
            throw new RuntimeException('Count query failed: ' . mysqli_error($this->connection));
        }
        $countRow = mysqli_fetch_assoc($countResult);
        $total = (int)($countRow['total'] ?? 0);
        mysqli_free_result($countResult);

        $sql = "SELECT p.id, p.name, p.sku, p.price, p.stock_quantity, p.status,
                       c.name as category_name, c.slug as category_slug
                FROM products p
                INNER JOIN categories c ON p.category_id = c.id
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Products query failed: ' . mysqli_error($this->connection));
        }

        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_free_result($result);

        return [
            'items' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    public function getPaginatedSearch(string $query, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        $searchQuery = mysqli_real_escape_string($this->connection, $query);

        $countSql = "SELECT COUNT(*) as total
                     FROM products
                     WHERE active = 1
                       AND (name LIKE '%{$searchQuery}%' OR description LIKE '%{$searchQuery}%')";

        $countResult = mysqli_query($this->connection, $countSql);
        $countRow = mysqli_fetch_assoc($countResult);
        $total = (int)($countRow['total'] ?? 0);
        mysqli_free_result($countResult);

        $sql = "SELECT id, name, sku, price, stock_quantity, status
                FROM products
                WHERE active = 1
                  AND (name LIKE '%{$searchQuery}%' OR description LIKE '%{$searchQuery}%')
                ORDER BY name ASC
                LIMIT {$perPage} OFFSET {$offset}";

        $result = mysqli_query($this->connection, $sql);

        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_free_result($result);

        return [
            'items' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'query' => $query,
        ];
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
