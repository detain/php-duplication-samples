<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopArticleRepository
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

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $searchPattern = "%{$query}%";
        $limit = (int)$limit;
        $offset = (int)$offset;

        $stmt = $this->connection->prepare(
            'SELECT id, title, slug, excerpt, content, author_id, published_at, status
             FROM articles
             WHERE (title LIKE ? OR content LIKE ? OR tags LIKE ?)
               AND status = ?
             ORDER BY published_at DESC
             LIMIT ? OFFSET ?'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $status = 'published';
        $stmt->bind_param('sssisi', $searchPattern, $searchPattern, $searchPattern, $status, $limit, $offset);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $articles = [];

        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }

        $stmt->close();

        return $articles;
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        $tagPattern = "%{$tag}%";

        $stmt = $this->connection->prepare(
            'SELECT a.id, a.title, a.slug, a.excerpt, a.author_id, a.published_at
             FROM articles a
             WHERE a.tags LIKE ?
               AND a.status = ?
             ORDER BY a.published_at DESC
             LIMIT ?'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $status = 'published';
        $stmt->bind_param('ssi', $tagPattern, $status, $limit);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $articles = [];

        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }

        $stmt->close();

        return $articles;
    }

    public function searchAdvanced(array $criteria): array
    {
        $conditions = [];
        $params = [];
        $types = '';

        if (!empty($criteria['query'])) {
            $conditions[] = '(title LIKE ? OR content LIKE ?)';
            $pattern = "%{$criteria['query']}%";
            $params[] = $pattern;
            $params[] = $pattern;
            $types .= 'ss';
        }

        if (!empty($criteria['author_id'])) {
            $conditions[] = 'author_id = ?';
            $params[] = (int)$criteria['author_id'];
            $types .= 'i';
        }

        if (!empty($criteria['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $criteria['status'];
            $types .= 's';
        }

        if (!empty($criteria['tag'])) {
            $conditions[] = 'tags LIKE ?';
            $params[] = "%{$criteria['tag']}%";
            $types .= 's';
        }

        if (empty($conditions)) {
            return [];
        }

        $whereClause = implode(' AND ', $conditions);
        $limit = (int)($criteria['limit'] ?? 20);
        $offset = (int)($criteria['offset'] ?? 0);

        $sql = "SELECT * FROM articles WHERE {$whereClause} ORDER BY published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $articles = [];

        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }

        $stmt->close();

        return $articles;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
