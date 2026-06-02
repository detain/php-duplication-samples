<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using Redis.
 * Demonstrates "Store and retrieve time series data" in Redis style.
 */
final class RedisTimeseriesRepository
{
    private \Redis $redis;
    private string $keyPrefix;

    public function __construct(\Redis $redis, string $keyPrefix = 'timeseries')
    {
        $this->redis = $redis;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Add data point to time series.
     *
     * @param string $seriesKey
     * @param float $value
     * @param int|null $timestamp
     * @return bool
     */
    public function add(string $seriesKey, float $value, ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? (int) (microtime(true) * 1000);
        $key = $this->keyPrefix . ':' . $seriesKey;

        try {
            $this->redis->zadd($key, [$timestamp => $value]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get range of data points.
     *
     * @param string $seriesKey
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function range(string $seriesKey, int $startTime, int $endTime): array
    {
        $key = $this->keyPrefix . ':' . $seriesKey;

        $results = $this->redis->zRangeByScore($key, $startTime, $endTime, [
            'withscores' => true,
        ]);

        $data = [];

        foreach ($results as $timestamp => $value) {
            $data[] = [
                'timestamp' => (int) $timestamp,
                'value' => (float) $value,
            ];
        }

        return $data;
    }

    /**
     * Get latest N data points.
     *
     * @param string $seriesKey
     * @param int $count
     * @return array
     */
    public function latest(string $seriesKey, int $count = 10): array
    {
        $key = $this->keyPrefix . ':' . $seriesKey;

        $results = $this->redis->zRevRange($key, 0, $count - 1, true);

        $data = [];

        foreach ($results as $timestamp => $value) {
            $data[] = [
                'timestamp' => (int) $timestamp,
                'value' => (float) $value,
            ];
        }

        return $data;
    }

    /**
     * Get aggregated data over time bucket.
     *
     * @param string $seriesKey
     * @param int $startTime
     * @param int $endTime
     * @param string $aggregation
     * @param int $bucketSizeMs
     * @return array
     */
    public function aggregate(
        string $seriesKey,
        int $startTime,
        int $endTime,
        string $aggregation = 'avg',
        int $bucketSizeMs = 60000
    ): array {
        $key = $this->keyPrefix . ':' . $seriesKey;

        $rawData = $this->redis->zRangeByScore($key, $startTime, $endTime, ['withscores' => true]);

        if (empty($rawData)) {
            return [];
        }

        $buckets = [];

        foreach ($rawData as $timestamp => $value) {
            $bucketKey = (int) (((int) $timestamp / $bucketSizeMs) * $bucketSizeMs);

            if (!isset($buckets[$bucketKey])) {
                $buckets[$bucketKey] = [];
            }

            $buckets[$bucketKey][] = (float) $value;
        }

        $result = [];

        foreach ($buckets as $bucketTimestamp => $values) {
            $result[] = [
                'timestamp' => $bucketTimestamp,
                'value' => $this->aggregateValues($values, $aggregation),
            ];
        }

        usort($result, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $result;
    }

    private function aggregateValues(array $values, string $aggregation): float
    {
        return match ($aggregation) {
            'avg' => array_sum($values) / count($values),
            'sum' => array_sum($values),
            'min' => min($values),
            'max' => max($values),
            'count' => count($values),
            default => array_sum($values) / count($values),
        };
    }

    /**
     * Delete time series.
     *
     * @param string $seriesKey
     * @return bool
     */
    public function delete(string $seriesKey): bool
    {
        $key = $this->keyPrefix . ':' . $seriesKey;

        try {
            $this->redis->delete($key);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
