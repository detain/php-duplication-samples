<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for aggregation operations.
 */
interface AggregatableUserRepositoryInterface
{
    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Calculate average posts per user.
     *
     * @return float
     */
    public function averagePostsPerUser(): float;

    /**
     * Sum of total views across all posts.
     *
     * @return int
     */
    public function sumPostViews(): int;

    /**
     * Get user statistics.
     *
     * @return StatisticsDTO
     */
    public function getUserStatistics(): StatisticsDTO;

    /**
     * Get top users by post count.
     *
     * @param int $limit
     * @return array<TopUserDTO>
     */
    public function getTopUsersByPostCount(int $limit = 10): array;
}

/**
 * Statistics data transfer object.
 */
final readonly class StatisticsDTO
{
    public function __construct(
        public int $totalUsers,
        public int $activeUsers,
        public int $inactiveUsers,
        public int $usersWithPosts,
        public int $usersWithoutPosts,
        public float $averagePostsPerUser,
    ) {}

    public function toArray(): array
    {
        return [
            'totalUsers' => $this->totalUsers,
            'activeUsers' => $this->activeUsers,
            'inactiveUsers' => $this->inactiveUsers,
            'usersWithPosts' => $this->usersWithPosts,
            'usersWithoutPosts' => $this->usersWithoutPosts,
            'averagePostsPerUser' => $this->averagePostsPerUser,
        ];
    }
}

/**
 * Top user data transfer object.
 */
final readonly class TopUserDTO
{
    public function __construct(
        public UserDTO $user,
        public int $postCount,
    ) {}

    public function toArray(): array
    {
        return [
            'user' => $this->user->toArray(),
            'postCount' => $this->postCount,
        ];
    }
}

/**
 * Aggregation result enum.
 */
enum AggregationType
{
    case COUNT;
    case SUM;
    case AVG;
    case MIN;
    case MAX;
}

/**
 * Abstract base aggregation repository.
 */
abstract class AbstractAggregatableUserRepository implements AggregatableUserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function countByStatus(): array
    {
        return $this->doCountByStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function averagePostsPerUser(): float
    {
        return $this->doAveragePostsPerUser();
    }

    /**
     * {@inheritdoc}
     */
    public function sumPostViews(): int
    {
        return $this->doSumPostViews();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserStatistics(): StatisticsDTO
    {
        $counts = $this->countByStatus();
        $avgPosts = $this->averagePostsPerUser();

        $totalUsers = array_sum($counts);
        $activeUsers = $counts['active'] ?? 0;

        return new StatisticsDTO(
            totalUsers: $totalUsers,
            activeUsers: $activeUsers,
            inactiveUsers: $totalUsers - $activeUsers,
            usersWithPosts: $this->countUsersWithPosts(),
            usersWithoutPosts: $totalUsers - $this->countUsersWithPosts(),
            averagePostsPerUser: $avgPosts,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTopUsersByPostCount(int $limit = 10): array
    {
        return $this->doGetTopUsersByPostCount($limit);
    }

    /**
     * Perform count by status.
     *
     * @return array<string, int>
     */
    abstract protected function doCountByStatus(): array;

    /**
     * Perform average calculation.
     *
     * @return float
     */
    abstract protected function doAveragePostsPerUser(): float;

    /**
     * Perform sum.
     *
     * @return int
     */
    abstract protected function doSumPostViews(): int;

    /**
     * Count users with posts.
     *
     * @return int
     */
    abstract protected function countUsersWithPosts(): int;

    /**
     * Get top users by post count.
     *
     * @param int $limit
     * @return array<TopUserDTO>
     */
    abstract protected function doGetTopUsersByPostCount(int $limit): array;
}

/**
 * Factory for creating aggregatable repositories.
 */
final class AggregatableUserRepositoryFactory
{
    public static function create(string $type, array $config = []): AggregatableUserRepositoryInterface
    {
        return match ($type) {
            'doctrine' => new \App\Database\Doctrine\DoctrineUserRepository(
                $config['entity_manager']
            ),
            'eloquent' => new \App\Database\Eloquent\EloquentUserRepository(),
            'propel' => new \App\Database\Propel\PropelUserRepository(
                $config['connection'] ?? null
            ),
            'cycle' => new \App\Database\Cycle\CycleUserRepository(
                $config['entity_manager']
            ),
            'yii' => new \App\Database\Yii\YiiUserRepository(),
            'cake' => new \App\Database\Cake\CakeUserRepository(
                $config['table'],
                $config['posts_table'] ?? null
            ),
            'redbean' => new \App\Database\RedBean\RedBeanUserRepository(
                $config['database'] ?? null
            ),
            default => throw new \RuntimeException("Unknown repository type: {$type}"),
        };
    }
}
