<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * User repository using RedBeanPHP ORM.
 * Demonstrates "Raw query fallback" operation when ORM queries are insufficient.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
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
            FROM user u
            LEFT JOIN post p ON u.id = p.author_id
            WHERE p.created_at BETWEEN ? AND ?
            GROUP BY u.id, u.name, u.email
            HAVING COUNT(p.id) > 0
            ORDER BY totalViews DESC
        SQL;

        try {
            return R::getAll($sql, [
                $params['startDate']->format('Y-m-d H:i:s'),
                $params['endDate']->format('Y-m-d H:i:s'),
            ]);
        } catch (RedException $e) {
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
                FROM user u
                LEFT JOIN post p ON u.id = p.author_id
                WHERE p.created_at BETWEEN ? AND ?
                GROUP BY u.id, u.name
            ) ranked
            ORDER BY rank ASC
        SQL;

        try {
            return R::getAll($sql, [$startDate, $endDate]);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to execute raw ranking query: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Execute complex search with MATCH AGAINST.
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
                MATCH(u.name, u.email) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
            FROM user u
            WHERE MATCH(u.name, u.email) AGAINST(? IN NATURAL LANGUAGE MODE)
            ORDER BY relevance DESC
            LIMIT 50
        SQL;

        try {
            return R::getAll($sql, [$searchTerm, $searchTerm]);
        } catch (RedException $e) {
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
                FROM category
                WHERE parent_id IS NULL AND id = ?

                UNION ALL

                SELECT
                    c.id as categoryId,
                    c.name as categoryName,
                    ct.depth + 1,
                    CONCAT(ct.path, ' > ', c.name)
                FROM category c
                INNER JOIN category_tree ct ON c.parent_id = ct.categoryId
            )
            SELECT categoryId, categoryName, depth, path
            FROM category_tree
            ORDER BY path
        SQL;

        try {
            return R::getAll($sql, [$rootCategoryId]);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to execute hierarchical query: ' . $e->Message(),
                0,
                $e
            );
        }
    }
}
