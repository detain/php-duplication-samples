<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Raw query fallback" operation when ORM queries are insufficient.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Execute raw SQL for complex reporting query.
     *
     * @param array{startDate: \DateTimeInterface, endDate: \DateTimeInterface} $params
     * @return array<array{userId: int, userName: string, email: string, postCount: int, totalViews: int}>
     */
    public function getUserReportingStats(array $params): array
    {
        $sql = <<<SQL
            SELECT
                u.id as userId,
                u.name as userName,
                u.email,
                COUNT(p.id) as postCount,
                COALESCE(SUM(p.view_count), 0) as totalViews
            FROM users u
            LEFT JOIN posts p ON u.id = p.author_id
            WHERE p.created_at BETWEEN :startDate AND :endDate
            GROUP BY u.id, u.name, u.email
            HAVING COUNT(p.id) > 0
            ORDER BY totalViews DESC
        SQL;

        try {
            $conn = $this->em->getConnection();
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([
                'startDate' => $params['startDate']->format('Y-m-d H:i:s'),
                'endDate' => $params['endDate']->format('Y-m-d H:i:s'),
            ]);

            return $result->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException(
                'Failed to execute raw query: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Execute raw SQL with window functions for rankings.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array<array{userId: int, userName: string, rank: int, postCount: int, avgViews: float}>
     */
    public function getUserRankingsWithWindowFunction(string $startDate, string $endDate): array
    {
        $sql = <<<SQL
            SELECT
                userId,
                userName,
                RANK() OVER (ORDER BY postCount DESC) as rank,
                postCount,
                avgViews
            FROM (
                SELECT
                    u.id as userId,
                    u.name as userName,
                    COUNT(p.id) as postCount,
                    AVG(p.view_count) as avgViews
                FROM users u
                LEFT JOIN posts p ON u.id = p.author_id
                WHERE p.created_at BETWEEN :startDate AND :endDate
                GROUP BY u.id, u.name
            ) ranked
            ORDER BY rank ASC
        SQL;

        try {
            $conn = $this->em->getConnection();
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);

            return $result->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException(
                'Failed to execute raw ranking query: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Execute complex search with fulltext match.
     *
     * @param string $searchTerm
     * @return array<array{userId: int, userName: string, email: string, relevance: float}>
     */
    public function fulltextSearchUsers(string $searchTerm): array
    {
        $sql = <<<SQL
            SELECT
                u.id as userId,
                u.name as userName,
                u.email,
                MATCH(name, email) AGAINST(:searchTerm IN NATURAL LANGUAGE MODE) as relevance
            FROM users u
            WHERE MATCH(name, email) AGAINST(:searchTerm IN NATURAL LANGUAGE MODE)
            ORDER BY relevance DESC
            LIMIT 50
        SQL;

        try {
            $conn = $this->em->getConnection();
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([
                'searchTerm' => $searchTerm,
            ]);

            return $result->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException(
                'Failed to execute fulltext search: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Execute recursive CTE for hierarchical data.
     *
     * @param int $rootCategoryId
     * @return array<array{categoryId: int, categoryName: string, depth: int, path: string}>
     */
    public function getCategoryHierarchy(int $rootCategoryId): array
    {
        $sql = <<<SQL
            WITH RECURSIVE category_tree AS (
                SELECT
                    id as categoryId,
                    name as categoryName,
                    0 as depth,
                    CAST(name AS CHAR(1000)) as path
                FROM categories
                WHERE parent_id IS NULL AND id = :rootId

                UNION ALL

                SELECT
                    c.id as categoryId,
                    c.name as categoryName,
                    ct.depth + 1,
                    CONCAT(ct.path, ' > ', c.name)
                FROM categories c
                INNER JOIN category_tree ct ON c.parent_id = ct.categoryId
            )
            SELECT categoryId, categoryName, depth, path
            FROM category_tree
            ORDER BY path
        SQL;

        try {
            $conn = $this->em->getConnection();
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([
                'rootId' => $rootCategoryId,
            ]);

            return $result->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException(
                'Failed to execute hierarchical query: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Execute cross-database query using native driver.
     *
     * @param string $externalDatabase
     * @param int $userId
     * @return array<string, mixed>|null
     */
    public function getExternalDatabaseUserData(string $externalDatabase, int $userId): ?array
    {
        $sql = <<<SQL
            SELECT u.id, u.name, u.email, u.created_at
            FROM {$externalDatabase}.users u
            WHERE u.id = :userId
        SQL;

        try {
            $conn = $this->em->getConnection();
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([
                'userId' => $userId,
            ]);

            $row = $result->fetchAssociative();

            return $row ?: null;
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException(
                'Failed to query external database: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
