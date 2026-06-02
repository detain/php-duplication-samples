<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using InfluxDB.
 * Demonstrates "Store and retrieve time series data" in InfluxDB style.
 */
final class InfluxDBTimeseriesRepository
{
    private \InfluxDB\Client $client;
    private string $database;
    private string $retentionPolicy;

    public function __construct(
        \InfluxDB\Client $client,
        string $database = 'timeseries',
        string $retentionPolicy = 'autogen'
    ) {
        $this->client = $client;
        $this->database = $database;
        $this->retentionPolicy = $retentionPolicy;
    }

    /**
     * Add data point to time series.
     *
     * @param string $measurement
     * @param array $tags
     * @param array $fields
     * @param int|null $timestamp
     * @return bool
     */
    public function add(
        string $measurement,
        array $tags,
        array $fields,
        ?int $timestamp = null
    ): bool {
        $timestamp = $timestamp ?? (int) (microtime(true) * 1000000000);

        $points = [
            new \InfluxDB\Point(
                $measurement,
                $fields['value'] ?? 0,
                $tags,
                [],
                $timestamp
            ),
        ];

        try {
            $this->client->write($points, $this->retentionPolicy, $this->database);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get range of data points.
     *
     * @param string $measurement
     * @param array $tags
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function range(
        string $measurement,
        array $tags = [],
        int $startTime = 0,
        int $endTime = 0
    ): array {
        $start = $startTime > 0 ? date('Y-m-d\TH:i:s\Z', $startTime / 1000) : '1970-01-01T00:00:00Z';
        $end = $endTime > 0 ? date('Y-m-d\TH:i:s\Z', $endTime / 1000) : 'now()';

        $query = "SELECT * FROM \"{$measurement}\" WHERE time >= '{$start}' AND time <= '{$end}'";

        if (!empty($tags)) {
            $tagConditions = [];

            foreach ($tags as $key => $value) {
                $tagConditions[] = "{$key} = '{$value}'";
            }

            $query .= ' AND ' . implode(' AND ', $tagConditions);
        }

        try {
            $result = $this->client->query($query, $this->database);
            $series = $result->getSeries();

            if (empty($series)) {
                return [];
            }

            $data = [];

            foreach ($series[0]['values'] as $point) {
                $data[] = [
                    'timestamp' => strtotime($point[0]) * 1000,
                    'fields' => array_combine(
                        $series[0]['columns'],
                        array_slice($point, 1)
                    ),
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
     * @param string $measurement
     * @param array $tags
     * @param int $limit
     * @return array
     */
    public function latest(string $measurement, array $tags = [], int $limit = 10): array
    {
        $query = "SELECT LAST(*) FROM \"{$measurement}\"";

        if (!empty($tags)) {
            $tagConditions = [];

            foreach ($tags as $key => $value) {
                $tagConditions[] = "{$key} = '{$value}'";
            }

            $query .= ' WHERE ' . implode(' AND ', $tagConditions);
        }

        $query .= " GROUP BY * LIMIT {$limit}";

        try {
            $result = $this->client->query($query, $this->database);
            $series = $result->getSeries();

            if (empty($series)) {
                return [];
            }

            $data = [];

            foreach ($series as $s) {
                foreach ($s['values'] as $point) {
                    $data[] = [
                        'timestamp' => strtotime($point[0]) * 1000,
                        'fields' => array_combine($s['columns'], array_slice($point, 1)),
                    ];
                }
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get aggregated data over time bucket.
     *
     * @param string $measurement
     * @param array $tags
     * @param int $startTime
     * @param int $endTime
     * @param string $aggregation
     * @param string $bucket
     * @return array
     */
    public function aggregate(
        string $measurement,
        array $tags = [],
        int $startTime = 0,
        int $endTime = 0,
        string $aggregation = 'mean',
        string $bucket = '1m'
    ): array {
        $start = $startTime > 0 ? date('Y-m-d\TH:i:s\Z', $startTime / 1000) : '1970-01-01T00:00:00Z';
        $end = $endTime > 0 ? date('Y-m-d\TH:i:s\Z', $endTime / 1000) : 'now()';

        $query = "SELECT {$aggregation}(value) FROM \"{$measurement}\" ";
        $query .= "WHERE time >= '{$start}' AND time <= '{$end}' ";
        $query .= "GROUP BY time({$bucket})";

        if (!empty($tags)) {
            $tagConditions = [];

            foreach ($tags as $key => $value) {
                $tagConditions[] = "{$key} = '{$value}'";
            }

            $query .= ', ' . implode(', ', array_keys($tags));
        }

        try {
            $result = $this->client->query($query, $this->database);
            $series = $result->getSeries();

            if (empty($series)) {
                return [];
            }

            $data = [];

            foreach ($series[0]['values'] as $point) {
                if ($point[1] !== null) {
                    $data[] = [
                        'timestamp' => strtotime($point[0]) * 1000,
                        'value' => (float) $point[1],
                    ];
                }
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }
}
