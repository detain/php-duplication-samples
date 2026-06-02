<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use App\Entity\User;
use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * User repository using RedBeanPHP ORM.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        try {
            $beans = R::find('user', '1=1 GROUP BY status');

            $counts = [];
            foreach ($beans as $bean) {
                $status = $bean->status ?? 'unknown';
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }

            return $counts;
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to count users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Calculate average posts per user.
     *
     * @return float
     */
    public function averagePostsPerUser(): float
    {
        try {
            $postCount = R::count('post');
            $userCount = R::count('user');

            if ($userCount === 0) {
                return 0.0;
            }

            return round($postCount / $userCount, 2);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to calculate average: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Sum of total views across all posts.
     *
     * @return int
     */
    public function sumPostViews(): int
    {
        try {
            $result = R::getRow('SELECT SUM(view_count) as total FROM post');

            return (int) ($result['total'] ?? 0);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to sum views: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get user statistics.
     *
     * @return array{
     *     totalUsers: int,
     *     activeUsers: int,
     *     inactiveUsers: int,
     *     usersWithPosts: int,
     *     usersWithoutPosts: int,
     *     averagePostsPerUser: float
     * }
     */
    public function getUserStatistics(): array
    {
        try {
            $totalUsers = R::count('user');
            $activeUsers = R::count('user', 'status = ?', ['active']);
            $usersWithPosts = R::count('post', '1=1 GROUP BY author_id');

            return [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'inactiveUsers' => $totalUsers - $activeUsers,
                'usersWithPosts' => $usersWithPosts,
                'usersWithoutPosts' => $totalUsers - $usersWithPosts,
                'averagePostsPerUser' => $this->averagePostsPerUser(),
            ];
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to get statistics: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get top users by post count.
     *
     * @param int $limit
     * @return array<array{user: User, postCount: int}>
     */
    public function getTopUsersByPostCount(int $limit = 10): array
    {
        try {
            $beans = R::find('user',
                '1=1 ORDER BY (SELECT COUNT(*) FROM post WHERE post.author_id = user.id) DESC LIMIT ?',
                [$limit]
            );

            return array_map(function ($bean) {
                $postCount = R::count('post', 'author_id = ?', [$bean->id]);
                return [
                    'user' => $this->mapToEntity($bean),
                    'postCount' => (int) $postCount,
                ];
            }, $beans);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function mapToEntity(\RedBeanPHP\OODBBean $bean): User
    {
        $user = new User();
        $user->id = (int) $bean->id;
        $user->email = $bean->email;
        $user->name = $bean->name ?? '';
        $user->createdAt = isset($bean->createdAt)
            ? new \DateTime($bean->createdAt)
            : new \DateTime();

        return $user;
    }
}
