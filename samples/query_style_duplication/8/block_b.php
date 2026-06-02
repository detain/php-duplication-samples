<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopPaginationRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);
        if ($this->connection->connect_error) {
            throw new RuntimeException('Connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
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

        $params = [];
        $types = '';

        if ($category !== null && $category !== '') {
            $countSql .= ' AND c.slug = ?';
            $params[] = $category;
            $types .= 's';
        }

        $countStmt = $this->connection->prepare($countSql);

        if ($countStmt === false) {
            throw new RuntimeException('Count prepare failed: ' . $this->connection->error);
        }

        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }

        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $total = (int)($countRow['total'] ?? 0);
        $countStmt->close();

        $sql = 'SELECT p.id, p.name, p.sku, p.price, p.stock_quantity, p.status,
                       c.name as category_name, c.slug as category_slug
                FROM products p
                INNER JOIN categories c ON p.category_id = c.id
                WHERE p.active = 1';

        if ($category !== null && $category !== '') {
            $sql .= ' AND c.slug = ?';
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        if ($category !== null && $category !== '') {
            $stmt->bind_param('sii', $category, $perPage, $offset);
        } else {
            $stmt->bind_param('ii', $perPage, $offset);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        $stmt->close();

        return [
            'items' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    public function getPaginatedWithCursors(int $cursor = 0, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $sql = 'SELECT id, name, sku, price, created_at
                FROM products
                WHERE active = 1 AND id > ?
                ORDER BY id ASC
                LIMIT ?';

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('ii', $cursor, $limit);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();

        $nextCursor = !empty($items) ? (int)end($items)['id'] : $cursor;

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => count($items) === $limit,
        ];
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
