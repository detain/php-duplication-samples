<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\Select\Repository;
use App\Entity\User;
use App\Entity\Post;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class CycleUserRepository extends Repository
{
    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        try {
            $results = $this->select()
                ->groupBy('status')
                ->fetchAll();

            $counts = [];
            foreach ($results as $user) {
                $status = $user->status ?? 'unknown';
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }

            return $counts;
        } catch (\Exception $e) {
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
            $postRepository = $this->em->getRepository(Post::class);
            $postCount = $postRepository->select()->count();
            $userCount = $this->select()->count();

            if ($userCount === 0) {
                return 0.0;
            }

            return round($postCount / $userCount, 2);
        } catch (\Exception $e) {
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
            $postRepository = $this->em->getRepository(Post::class);
            $result = $postRepository->select()->sum('viewCount')->fetchOne();

            return (int) ($result ?? 0);
        } catch (\Exception $e) {
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
        $totalUsers = $this->select()->count();
        $activeUsers = $this->select()->where('status', 'active')->count();

        $postRepository = $this->em->getRepository(Post::class);
        $usersWithPosts = $postRepository->select()
            ->with('author')
            ->groupBy('author_id')
            ->count();

        return [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $totalUsers - $activeUsers,
            'usersWithPosts' => $usersWithPosts,
            'usersWithoutPosts' => $totalUsers - $usersWithPosts,
            'averagePostsPerUser' => $this->averagePostsPerUser(),
        ];
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
            $results = $this->select()
                ->with('posts')
                ->orderBy('posts', 'DESC')
                ->limit($limit)
                ->fetchAll();

            return array_map(function ($user) {
                return [
                    'user' => $user,
                    'postCount' => count($user->posts ?? []),
                ];
            }, $results);
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
