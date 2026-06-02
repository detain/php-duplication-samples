<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Table\PostsTable;
use App\Model\Entity\User as UserEntity;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class CakeUserRepository
{
    private UsersTable $table;
    private PostsTable $postsTable;

    public function __construct(UsersTable $table, PostsTable $postsTable)
    {
        $this->table = $table;
        $this->postsTable = $postsTable;
    }

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        try {
            $results = $this->table->find()
                ->select(['status', 'count' => $this->table->find()->func()->count('*')])
                ->groupBy('status')
                ->all();

            $counts = [];
            foreach ($results as $row) {
                $counts[$row->status] = (int) $row->count;
            }

            return $counts;
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
            $postCount = $this->postsTable->find()->count();
            $userCount = $this->table->find()->count();

            if ($userCount === 0) {
                return 0.0;
            }

            return round($postCount / $userCount, 2);
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
            $result = $this->postsTable->find()
                ->select(['total' => $this->postsTable->find()->func()->sum('view_count')])
                ->first();

            return (int) ($result->total ?? 0);
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
            $totalUsers = $this->table->find()->count();
            $activeUsers = $this->table->find()->where(['status' => 'active'])->count();
            $usersWithPosts = $this->table->find()
                ->innerJoinWith('Posts')
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
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
     * @return array<array{user: UserEntity, postCount: int}>
     */
    public function getTopUsersByPostCount(int $limit = 10): array
    {
        try {
            $users = $this->table->find()
                ->select([
                    'id',
                    'name',
                    'email',
                    'status',
                    'post_count' => $this->table->find()->func()->count('Posts.id'),
                ])
                ->leftJoinWith('Posts')
                ->groupBy(['id'])
                ->orderBy('post_count', 'DESC')
                ->limit($limit)
                ->all();

            return array_map(fn($user) => [
                'user' => $user,
                'postCount' => (int) $user->post_count,
            ], $users->toArray());
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
