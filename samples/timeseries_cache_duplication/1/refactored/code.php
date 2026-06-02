<?php
declare(strict_types=1);

namespace App\Database\Timeseries\Refactored;

/**
 * Timeseries data point.
 */
final readonly class TimeseriesPoint
{
    public function __construct(
        public int $timestamp,
        public float $value,
        public array $tags = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: $data['timestamp'] ?? 0,
            value: (float) ($data['value'] ?? 0),
            tags: $data['tags'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'value' => $this->value,
            'tags' => $this->tags,
        ];
    }
}

/**
 * Aggregation type enum.
 */
enum AggregationType: string
{
    case AVG = 'avg';
    case SUM = 'sum';
    case MIN = 'min';
    case MAX = 'max';
    case COUNT = 'count';
}

/**
 * Interface for timeseries repositories.
 */
interface TimeseriesRepositoryInterface
{
    /**
     * Add data point to time series.
     *
     * @param string $seriesKey
     * @param float $value
     * @param int|null $timestamp
     * @param array $tags
     * @return bool
     */
    public function add(string $seriesKey, float $value, ?int $timestamp = null, array $tags = []): bool;

    /**
     * Get range of data points.
     *
     * @param string $seriesKey
     * @param int $startTime
     * @param int $endTime
     * @return array<TimeseriesPoint>
     */
    public function range(string $seriesKey, int $startTime, int $endTime): array;

    /**
     * Get latest N data points.
     *
     * @param string $seriesKey
     * @param int $count
     * @return array<TimeseriesPoint>
     */
    public function latest(string $seriesKey, int $count = 10): array;

    /**
     * Get aggregated data over time bucket.
     *
     * @param string $seriesKey
     * @param int $startTime
     * @param int $endTime
     * @param AggregationType $aggregation
     * @param string $bucket
     * @return array<TimeseriesPoint>
     */
    public function aggregate(
        string $seriesKey,
        int $startTime,
        int $endTime,
        AggregationType $aggregation = AggregationType::AVG,
        string $bucket = '1m'
    ): array;
}

/**
 * Abstract base for timeseries repositories.
 */
abstract class AbstractTimeseriesRepository implements TimeseriesRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function add(string $seriesKey, float $value, ?int $timestamp = null, array $tags = []): bool
    {
        $timestamp = $timestamp ?? $this->getCurrentTimestamp();

        return $this->doAdd($seriesKey, $value, $timestamp, $tags);
    }

    /**
     * {@inheritdoc}
     */
    public function range(string $seriesKey, int $startTime, int $endTime): array
    {
        return $this->doRange($seriesKey, $startTime, $endTime);
    }

    /**
     * {@inheritdoc}
     */
    public function latest(string $seriesKey, int $count = 10): array
    {
        return $this->doLatest($seriesKey, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function aggregate(
        string $seriesKey,
        int $startTime,
        int $endTime,
        AggregationType $aggregation = AggregationType::AVG,
        string $bucket = '1m'
    ): array {
        return $this->doAggregate($seriesKey, $startTime, $endTime, $aggregation->value, $bucket);
    }

    /**
     * Get current timestamp in milliseconds.
     */
    protected function getCurrentTimestamp(): int
    {
        return (int) (microtime(true) * 1000);
    }

    abstract protected function doAdd(string $seriesKey, float $value, int $timestamp, array $tags): bool;

    abstract protected function doRange(string $seriesKey, int $startTime, int $endTime): array;

    abstract protected function doLatest(string $seriesKey, int $count): array;

    abstract protected function doAggregate(
        string $seriesKey,
        int $startTime,
        int $endTime,
        string $aggregation,
        string $bucket
    ): array;
}

/**
 * Factory for timeseries repositories.
 */
final class TimeseriesRepositoryFactory
{
    public static function create(string $type, array $config = []): TimeseriesRepositoryInterface
    {
        return match ($type) {
            'redis' => new \App\Database\Timeseries\RedisTimeseriesRepository($config['redis']),
            'influxdb' => new \App\Database\Timeseries\InfluxDBTimeseriesRepository(
                $config['client'],
                $config['database'] ?? 'timeseries'
            ),
            'timescaledb' => new \App\Database\Timeseries\TimescaleDBTimeseriesRepository(
                $config['pdo'],
                $config['table'] ?? 'timeseries_data'
            ),
            default => throw new \RuntimeException("Unknown timeseries type: {$type}"),
        };
    }
}
