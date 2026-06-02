<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using ClickHouse.
 * Demonstrates "Store and retrieve time series data" in ClickHouse style.
 */
final class ClickHouseTimeseriesRepository
{
    private \ClickHouse\Client $client;
    private string $tableName;

    public function __construct(\ClickHouse\Client $client, string $tableName = 'timeseries')
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    /**
     * Add data point to time series.
     *
     * @param string $metric
     * @param float $value
     * @param array $tags
     * @param int|null $timestamp
     * @return bool
     */
    public function add(string $metric, float $value, array $tags = [], ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? time();

        $row = [
            'metric' => $metric,
            'value' => $value,
            'timestamp' => $timestamp,
            'tags' => json_encode($tags),
        ];

        try {
            $this->client->insert($this->tableName, [$row]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get range of data points.
     *
     * @param string $metric
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function range(string $metric, int $startTime, int $endTime): array
    {
        $query = "SELECT timestamp, value, tags FROM {$this->tableName} ";
        $query .= "WHERE metric = '{$metric}' ";
        $query .= "AND timestamp >= {$startTime} AND timestamp <= {$endTime} ";
        $query .= "ORDER BY timestamp ASC";

        try {
            $result = $this->client->select($query);

            $data = [];

            foreach ($result as $row) {
                $data[] = [
                    'timestamp' => (int) $row['timestamp'],
                    'value' => (float) $row['value'],
                    'tags' => json_decode($row['tags'], true),
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get latest N data points.
     *
     * @param string $metric
     * @param int $limit
     * @return array
     */
    public function latest(string $metric, int $limit = 10): array
    {
        $query = "SELECT timestamp, value, tags FROM {$this->tableName} ";
        $query .= "WHERE metric = '{$metric}' ";
        $query .= "ORDER BY timestamp DESC ";
        $query .= "LIMIT {$limit}";

        try {
            $result = $this->client->select($query);

            $data = [];

            foreach ($result as $row) {
                $data[] = [
                    'timestamp' => (int) $row['timestamp'],
                    'value' => (float) $row['value'],
                    'tags' => json_decode($row['tags'], true),
                ];
            }

            return array_reverse($data);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get aggregated data over time bucket.
     *
     * @param string $metric
     * @param int $startTime
     * @param int $endTime
     * @param string $aggregation
     * @param string $bucket
     * @return array
     */
    public function aggregate(
        string $metric,
        int $startTime,
        int $endTime,
        string $aggregation = 'avg',
        string $bucket = 'toStartOfMinute'
    ): array {
        $query = "SELECT ";
        $query .= "{$bucket}(toDateTime(timestamp)) AS bucket, ";
        $query .= "{$aggregation}(value) AS value ";
        $query .= "FROM {$this->tableName} ";
        $query .= "WHERE metric = '{$metric}' ";
        $query .= "AND timestamp >= {$startTime} AND timestamp <= {$endTime} ";
        $query .= "GROUP BY bucket ";
        $query .= "ORDER BY bucket ASC";

        try {
            $result = $this->client->select($query);

            $data = [];

            foreach ($result as $row) {
                $data[] = [
                    'timestamp' => strtotime($row['bucket']) * 1000,
                    'value' => (float) $row['value'],
                ];
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }
}
