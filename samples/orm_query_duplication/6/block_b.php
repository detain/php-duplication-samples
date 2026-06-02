<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class User extends Model
{
    protected $table = 'users';

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public static function countByStatus(): array
    {
        try {
            $results = static::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();

            $counts = [];
            foreach ($results as $row) {
                $counts[$row->status] = (int) $row->count;
            }

            return $counts;
        } catch (\Exception $e) {
            throw new \RuntimeException(
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
    public static function averagePostsPerUser(): float
    {
        try {
            $stats = Post::select(
                DB::raw('COUNT(*) as postCount'),
                DB::raw('COUNT(DISTINCT author_id) as userCount')
            )->first();

            if ($stats->userCount === 0) {
                return 0.0;
            }

            return round($stats->postCount / $stats->userCount, 2);
        } catch (\Exception $e) {
            throw new \RuntimeException(
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
    public static function sumPostViews(): int
    {
        try {
            $result = Post::sum('view_count');

            return (int) ($result ?? 0);
        } catch (\Exception $e) {
            throw new \RuntimeException(
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
    public static function getUserStatistics(): array
    {
        try {
            $totalUsers = static::count();
            $activeUsers = static::where('status', 'active')->count();
            $usersWithPosts = static::has('posts')->count();

            return [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'inactiveUsers' => $totalUsers - $activeUsers,
                'usersWithPosts' => $usersWithPosts,
                'usersWithoutPosts' => $totalUsers - $usersWithPosts,
                'averagePostsPerUser' => static::averagePostsPerUser(),
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
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
    public static function getTopUsersByPostCount(int $limit = 10): array
    {
        try {
            $users = static::withCount('posts')
                ->orderBy('posts_count', 'DESC')
                ->limit($limit)
                ->get();

            return $users->map(fn($user) => [
                'user' => $user,
                'postCount' => $user->posts_count,
            ])->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get posts relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
