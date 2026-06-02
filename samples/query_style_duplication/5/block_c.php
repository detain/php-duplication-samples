<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareArticleRepository
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
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':query', $searchPattern);
            $stmt->bindValue(':query2', $searchPattern);
            $stmt->bindValue(':query3', $searchPattern);
            $stmt->bindValue(':status', 'published');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new RuntimeException('Search failed: ' . $e->getMessage(), 0, $e);
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
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':tag', $tagPattern);
            $stmt->bindValue(':status', 'published');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new RuntimeException('Tag search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchByDateRange(
        string $query,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        int $limit = 20
    ): array {
        $searchPattern = "%{$query}%";

        $sql = 'SELECT id, title, slug, excerpt, author_id, published_at, status
                FROM articles
                WHERE (title LIKE :query OR content LIKE :query2)
                  AND published_at BETWEEN :start_date AND :end_date
                  AND status = :status
                ORDER BY published_at DESC
                LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':query', $searchPattern);
            $stmt->bindValue(':query2', $searchPattern);
            $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue(':status', 'published');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new RuntimeException('Date range search failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
