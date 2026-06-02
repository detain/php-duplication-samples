<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperArticleRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $tableName = $this->tablePrefix . 'articles';
        $searchPattern = "%{$query}%";

        $sql = <<<SQL
            SELECT id, title, slug, excerpt, content, author_id, published_at, status
            FROM {$tableName}
            WHERE (title LIKE :q1 OR content LIKE :q2 OR tags LIKE :q3)
              AND status = :status
            ORDER BY published_at DESC
            LIMIT :limit OFFSET :offset
SQL;

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
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Search failed in {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        $tableName = $this->tablePrefix . 'articles';
        $tagPattern = "%{$tag}%";

        $sql = <<<SQL
            SELECT a.id, a.title, a.slug, a.excerpt, a.author_id, a.published_at
            FROM {$tableName} a
            WHERE a.tags LIKE :tag
              AND a.status = :status
            ORDER BY a.published_at DESC
            LIMIT :limit
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':tag', $tagPattern);
            $stmt->bindValue(':status', 'published');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Tag search failed in {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function searchSimilar(int $articleId, int $limit = 5): array
    {
        $tableName = $this->tablePrefix . 'articles';

        $sql = <<<SQL
            SELECT a2.id, a2.title, a2.slug, a2.excerpt, a2.published_at,
                   COUNT(DISTINCT a2.tags) as tag_overlap
            FROM {$tableName} a1
            JOIN {$tableName} a2 ON a1.tags = a2.tags
            WHERE a1.id = :article_id
              AND a2.id != :article_id
              AND a2.status = 'published'
            GROUP BY a2.id, a2.title, a2.slug, a2.excerpt, a2.published_at
            ORDER BY tag_overlap DESC, a2.published_at DESC
            LIMIT :limit
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Similar articles search failed in {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
