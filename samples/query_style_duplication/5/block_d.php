<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalArticleRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $searchPattern = "%{$query}%";

        $sql = 'SELECT id, title, slug, excerpt, content, author_id, published_at, status
                FROM articles
                WHERE (title LIKE :query OR content LIKE :query2 OR tags LIKE :query3)
                  AND status = :status
                ORDER BY published_at DESC
                LIMIT :limit OFFSET :offset';

        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery([
                'query' => $searchPattern,
                'query2' => $searchPattern,
                'query3' => $searchPattern,
                'status' => 'published',
                'limit' => $limit,
                'offset' => $offset,
            ]);

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        $tagPattern = "%{$tag}%";

        $sql = 'SELECT a.id, a.title, a.slug, a.excerpt, a.author_id, a.published_at
                FROM articles a
                WHERE a.tags LIKE :tag
                  AND a.status = :status
                ORDER BY a.published_at DESC
                LIMIT :limit';

        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery([
                'tag' => $tagPattern,
                'status' => 'published',
                'limit' => $limit,
            ]);

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL tag search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchFullText(string $query, int $limit = 20): array
    {
        $words = explode(' ', $query);
        $conditions = [];
        $params = [];

        foreach ($words as $i => $word) {
            if (strlen($word) >= 3) {
                $conditions[] = "(title LIKE :w{$i} OR content LIKE :w{$i})";
                $params["w{$i}"] = "%{$word}%";
            }
        }

        if (empty($conditions)) {
            return [];
        }

        $whereClause = implode(' AND ', $conditions);
        $params['status'] = 'published';
        $params['limit'] = $limit;

        $sql = "SELECT * FROM articles
                WHERE {$whereClause} AND status = :status
                ORDER BY published_at DESC LIMIT :limit";

        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery($params);

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL full-text search failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
