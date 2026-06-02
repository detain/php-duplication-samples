<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use App\Models\Post as PostYii;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class YiiUserRepository
{
    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        try {
            $results = UserYii::find()
                ->select(['status', 'COUNT(*) as count'])
                ->groupBy('status')
                ->asArray()
                ->all();

            $counts = [];
            foreach ($results as $row) {
                $counts[$row['status']] = (int) $row['count'];
            }

            return $counts;
        } catch (Exception $e) {
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
            $stats = PostYii::find()
                ->select([
                    'COUNT(*) as postCount',
                    'COUNT(DISTINCT author_id) as userCount',
                ])
                ->asArray()
                ->one();

            if ($stats['userCount'] == 0) {
                return 0.0;
            }

            return round($stats['postCount'] / $stats['userCount'], 2);
        } catch (Exception $e) {
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
            $result = PostYii::find()->sum('view_count');

            return (int) ($result ?? 0);
        } catch (Exception $e) {
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
            $totalUsers = UserYii::find()->count();
            $activeUsers = UserYii::find()->where(['status' => 'active'])->count();
            $usersWithPosts = UserYii::find()
                ->joinWith('posts')
                ->groupBy('id')
                ->count();

            return [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'inactiveUsers' => $totalUsers - $activeUsers,
                'usersWithPosts' => $usersWithPosts,
                'usersWithoutPosts' => $totalUsers - $usersWithPosts,
                'averagePostsPerUser' => $this->averagePostsPerUser(),
            ];
        } catch (Exception $e) {
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
     * @return array<array{user: UserYii, postCount: int}>
     */
    public function getTopUsersByPostCount(int $limit = 10): array
    {
        try {
            $users = UserYii::find()
                ->select([
                    'id',
                    'name',
                    'email',
                    'status',
                    '(SELECT COUNT(*) FROM posts WHERE posts.author_id = user.id) as postCount',
                ])
                ->orderBy('postCount', SORT_DESC)
                ->limit($limit)
                ->all();

            return array_map(fn($user) => [
                'user' => $user,
                'postCount' => (int) $user->postCount,
            ], $users);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
