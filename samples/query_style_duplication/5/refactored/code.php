<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class ArticleRepository implements ArticleRepositoryInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'articles')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $searchPattern = "%{$query}%";

        $sql = "SELECT id, title, slug, excerpt, content, author_id, published_at, status
                FROM {$this->tableName}
                WHERE (title LIKE :q1 OR content LIKE :q2 OR tags LIKE :q3)
                  AND status = :status
                ORDER BY published_at DESC
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':q1', $searchPattern);
            $stmt->bindValue(':q2', $searchPattern);
            $stmt->bindValue(':q3', $searchPattern);
            $stmt->bindValue(':status', 'published');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Search failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        $tagPattern = "%{$tag}%";

        $sql = "SELECT id, title, slug, excerpt, author_id, published_at
                FROM {$this->tableName}
                WHERE tags LIKE :tag
                  AND status = :status
                ORDER BY published_at DESC
                LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':tag', $tagPattern);
            $stmt->bindValue(':status', 'published');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Tag search failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function searchWithFilters(array $filters, int $limit = 20, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['query'])) {
            $conditions[] = '(title LIKE :query OR content LIKE :query2)';
            $params['query'] = "%{$filters['query']}%";
            $params['query2'] = "%{$filters['query']}%";
        }

        if (!empty($filters['tag'])) {
            $conditions[] = 'tags LIKE :tag';
            $params['tag'] = "%{$filters['tag']}%";
        }

        if (!empty($filters['author_id'])) {
            $conditions[] = 'author_id = :author_id';
            $params['author_id'] = $filters['author_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT * FROM {$this->tableName} {$whereClause}
                ORDER BY published_at DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Filtered search failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
