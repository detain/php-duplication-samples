<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Statistics result.
 */
final readonly class AggregationResult
{
    public function __construct(
        public int $count,
        public float $total,
        public float $average,
        public float $min,
        public float $max,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0.0, 0.0, 0.0, 0.0);
    }

    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'total' => $this->total,
            'average' => $this->average,
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
}

/**
 * Interface for aggregation repositories.
 */
interface AggregationRepositoryInterface
{
    /**
     * Count documents.
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int;

    /**
     * Sum a field.
     *
     * @param string $field
     * @param array $conditions
     * @return float
     */
    public function sum(string $field, array $conditions = []): float;

    /**
     * Average a field.
     *
     * @param string $field
     * @param array $conditions
     * @return float
     */
    public function avg(string $field, array $conditions = []): float;

    /**
     * Get all statistics.
     *
     * @param string $field
     * @param array $conditions
     * @return AggregationResult
     */
    public function getStats(string $field, array $conditions = []): AggregationResult;
}

/**
 * Abstract base for aggregation repositories.
 */
abstract class AbstractAggregationRepository implements AggregationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getStats(string $field, array $conditions = []): AggregationResult
    {
        $count = $this->count($conditions);
        $total = $this->sum($field, $conditions);

        if ($count === 0) {
            return AggregationResult::empty();
        }

        return new AggregationResult(
            count: $count,
            total: $total,
            average: $total / $count,
            min: 0.0,
            max: 0.0,
        );
    }
}

/**
 * Factory for aggregation repositories.
 */
final class AggregationRepositoryFactory
{
    public static function create(string $type, array $config = []): AggregationRepositoryInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoAggregationRepository(
                $config['database']
            ),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchAggregationRepository(
                $config['client']
            ),
            'arangodb' => new \App\Database\NoSQL\ArangoDBAggregationRepository(
                $config['client']
            ),
            'dynamodb' => new \App\Database\NoSQL\DynamoDBAggregationRepository(
                $config['client']
            ),
            'firestore' => new \App\Database\NoSQL\FirestoreAggregationRepository(
                $config['client']
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
