<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using TimescaleDB.
 * Demonstrates "Store and retrieve time series data" in TimescaleDB style.
 */
final class TimescaleDBTimeseriesRepository
{
    private \PDO $pdo;
    private string $tableName;

    public function __construct(\PDO $pdo, string $tableName = 'timeseries_data')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    /**
     * Add data point to time series.
     *
     * @param string $seriesKey
     * @param float $value
     * @param array $tags
     * @param int|null $timestamp
     * @return bool
     */
    public function add(
        string $seriesKey,
        float $value,
        array $tags = [],
        ?int $timestamp = null
    ): bool {
        $timestamp = $timestamp ?? time();

        $sql = "INSERT INTO {$this->tableName} (series_key, timestamp, value, tags) ";
        $sql .= "VALUES (:seriesKey, :timestamp, :value, :tags)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'seriesKey' => $seriesKey,
                'timestamp' => date('Y-m-d H:i:s', $timestamp),
                'value' => $value,
                'tags' => json_encode($tags),
            ]);

            return true;
        } catch (\PDOException $e) {
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
        $sql = "SELECT timestamp, value, tags FROM {$this->tableName} ";
        $sql .= "WHERE series_key = :seriesKey ";
        $sql .= "AND timestamp >= :startTime AND timestamp <= :endTime ";
        $sql .= "ORDER BY timestamp ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'seriesKey' => $seriesKey,
                'startTime' => date('Y-m-d H:i:s', $startTime),
                'endTime' => date('Y-m-d H:i:s', $endTime),
            ]);

            $data = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = [
                    'timestamp' => strtotime($row['timestamp']) * 1000,
                    'value' => (float) $row['value'],
                    'tags' => json_decode($row['tags'], true),
                ];
            }

            return $data;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get latest N data points.
     *
     * @param string $seriesKey
     * @param int $limit
     * @return array
     */
    public function latest(string $seriesKey, int $limit = 10): array
    {
        $sql = "SELECT timestamp, value, tags FROM {$this->tableName} ";
        $sql .= "WHERE series_key = :seriesKey ";
        $sql .= "ORDER BY timestamp DESC ";
        $sql .= "LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('seriesKey', $seriesKey);
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            $data = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = [
                    'timestamp' => strtotime($row['timestamp']) * 1000,
                    'value' => (float) $row['value'],
                    'tags' => json_decode($row['tags'], true),
                ];
            }

            return array_reverse($data);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get aggregated data over time bucket.
     *
     * @param string $seriesKey
     * @param int $startTime
     * @param int $endTime
     * @param string $aggregation
     * @param string $bucket
     * @return array
     */
    public function aggregate(
        string $seriesKey,
        int $startTime,
        int $endTime,
        string $aggregation = 'avg',
        string $bucket = '1 minute'
    ): array {
        $sql = "SELECT ";
        $sql .= "time_bucket('{$bucket}', timestamp) AS bucket, ";
        $sql .= "{$aggregation}(value) AS value ";
        $sql .= "FROM {$this->tableName} ";
        $sql .= "WHERE series_key = :seriesKey ";
        $sql .= "AND timestamp >= :startTime AND timestamp <= :endTime ";
        $sql .= "GROUP BY bucket ";
        $sql .= "ORDER BY bucket ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'seriesKey' => $seriesKey,
                'startTime' => date('Y-m-d H:i:s', $startTime),
                'endTime' => date('Y-m-d H:i:s', $endTime),
            ]);

            $data = [];

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[] = [
                    'timestamp' => strtotime($row['bucket']) * 1000,
                    'value' => (float) $row['value'],
                ];
            }

            return $data;
        } catch (\PDOException $e) {
            return [];
        }
    }
}
