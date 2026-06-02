<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for raw query operations.
 */
interface RawQueryRepositoryInterface
{
    /**
     * Execute raw SQL for complex reporting query.
     *
     * @param array<string, mixed> $params
     * @return array<array<string, mixed>>
     */
    public function getUserReportingStats(array $params): array;

    /**
     * Execute raw SQL with window functions for rankings.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array<array<string, mixed>>
     */
    public function getUserRankingsWithWindowFunction(string $startDate, string $endDate): array;

    /**
     * Execute complex search with fulltext match.
     *
     * @param string $searchTerm
     * @return array<array<string, mixed>>
     */
    public function fulltextSearchUsers(string $searchTerm): array;

    /**
     * Execute recursive CTE for hierarchical data.
     *
     * @param int $rootId
     * @return array<array<string, mixed>>
     */
    public function getCategoryHierarchy(int $rootId): array;
}

/**
 * Query type enum.
 */
enum QueryType
{
    case SELECT;
    case INSERT;
    case UPDATE;
    case DELETE;
    case CALL;
    case CTE_RECURSION;
    case WINDOW_FUNCTION;
    case FULLTEXT;
}

/**
 * Raw query result data transfer object.
 */
final readonly class RawQueryResultDTO
{
    public function __construct(
        public array $data,
        public int $rowCount,
        public float $executionTimeMs,
    ) {}

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'rowCount' => $this->rowCount,
            'executionTimeMs' => $this->executionTimeMs,
        ];
    }
}

/**
 * Abstract base raw query repository.
 */
abstract class AbstractRawQueryRepository implements RawQueryRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserReportingStats(array $params): array
    {
        $startTime = microtime(true);

        $result = $this->executeReportingStatsQuery($params);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return [
            'data' => $result,
            'rowCount' => count($result),
            'executionTimeMs' => $executionTime,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRankingsWithWindowFunction(string $startDate, string $endDate): array
    {
        $startTime = microtime(true);

        $result = $this->executeRankingsQuery($startDate, $endDate);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return [
            'data' => $result,
            'rowCount' => count($result),
            'executionTimeMs' => $executionTime,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fulltextSearchUsers(string $searchTerm): array
    {
        $startTime = microtime(true);

        $result = $this->executeFulltextSearchQuery($searchTerm);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return [
            'data' => $result,
            'rowCount' => count($result),
            'executionTimeMs' => $executionTime,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryHierarchy(int $rootId): array
    {
        $startTime = microtime(true);

        $result = $this->executeHierarchyQuery($rootId);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return [
            'data' => $result,
            'rowCount' => count($result),
            'executionTimeMs' => $executionTime,
        ];
    }

    /**
     * Execute reporting stats query.
     *
     * @param array<string, mixed> $params
     * @return array<array<string, mixed>>
     */
    abstract protected function executeReportingStatsQuery(array $params): array;

    /**
     * Execute rankings query.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array<array<string, mixed>>
     */
    abstract protected function executeRankingsQuery(string $startDate, string $endDate): array;

    /**
     * Execute fulltext search query.
     *
     * @param string $searchTerm
     * @return array<array<string, mixed>>
     */
    abstract protected function executeFulltextSearchQuery(string $searchTerm): array;

    /**
     * Execute hierarchy query.
     *
     * @param int $rootId
     * @return array<array<string, mixed>>
     */
    abstract protected function executeHierarchyQuery(int $rootId): array;
}

/**
 * Factory for creating raw query repositories.
 */
final class RawQueryRepositoryFactory
{
    public static function create(string $type, array $config = []): RawQueryRepositoryInterface
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
                $config['table']
            ),
            'redbean' => new \App\Database\RedBean\RedBeanUserRepository(
                $config['database'] ?? null
            ),
            default => throw new \RuntimeException("Unknown repository type: {$type}"),
        };
    }
}
