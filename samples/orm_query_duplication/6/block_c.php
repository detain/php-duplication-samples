<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use App\Model\Post;
use App\Model\PostQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User repository using Propel ORM.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        try {
            $results = UserQuery::create()
                ->select(['Status', 'Id'])
                ->withColumn('COUNT(Id)', 'UserCount')
                ->groupBy('Status')
                ->find($this->connection);

            $counts = [];
            foreach ($results as $row) {
                $counts[$row['Status']] = (int) $row['UserCount'];
            }

            return $counts;
        } catch (PropelException $e) {
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
            $postCount = PostQuery::create()->count($this->connection);
            $userCount = UserQuery::create()->count($this->connection);

            if ($userCount === 0) {
                return 0.0;
            }

            return round($postCount / $userCount, 2);
        } catch (PropelException $e) {
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
            $sum = PostQuery::create()
                ->withColumn('SUM(ViewCount)', 'TotalViews')
                ->select(['TotalViews'])
                ->findOne($this->connection);

            return (int) ($sum['TotalViews'] ?? 0);
        } catch (PropelException $e) {
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
        $totalUsers = UserQuery::create()->count($this->connection);
        $activeUsers = UserQuery::create()
            ->filterByStatus('active')
            ->count($this->connection);

        $usersWithPosts = 0;
        $posts = PostQuery::create()->find($this->connection);
        $usersWithPostIds = [];
        foreach ($posts as $post) {
            $usersWithPostIds[$post->getUserId()] = true;
        }
        $usersWithPosts = count($usersWithPostIds);

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
     * @return array<array{user: UserPropel, postCount: int}>
     */
    public function getTopUsersByPostCount(int $limit = 10): array
    {
        try {
            $users = UserQuery::create()
                ->joinWithPost()
                ->withColumn('COUNT(Post.Id)', 'PostCount')
                ->groupBy('User.Id')
                ->orderBy('PostCount', 'desc')
                ->limit($limit)
                ->find($this->connection);

            return $users->map(function ($user) {
                return [
                    'user' => $user,
                    'postCount' => (int) $user->getVirtualColumn('PostCount'),
                ];
            })->toArray();
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
