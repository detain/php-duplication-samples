<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using QuestDB.
 * Demonstrates "Store and retrieve time series data" in QuestDB style.
 */
final class QuestDBTimeseriesRepository
{
    private \QuestDB\Client $client;
    private string $tableName;

    public function __construct(\QuestDB\Client $client, string $tableName = 'timeseries')
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    /**
     * Add data point to time series.
     *
     * @param string $symbol
     * @param float $value
     * @param array $tags
     * @param int|null $timestamp
     * @return bool
     */
    public function add(string $symbol, float $value, array $tags = [], ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? (int) (microtime(true) * 1000);

        $sql = "INSERT INTO {$this->tableName} (timestamp, symbol, value";
        $values = "VALUES ('{$timestamp}', '{$symbol}', {$value}";

        foreach ($tags as $key => $tagValue) {
            $sql .= ", {$key}";
            $values .= ", '{$tagValue}'";
        }

        $sql .= ") " . $values . ")";

        try {
            $this->client->exec($sql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get range of data points.
     *
     * @param string $symbol
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public function range(string $symbol, int $startTime, int $endTime): array
    {
        $sql = "SELECT timestamp, value FROM {$this->tableName} ";
        $sql .= "WHERE symbol = '{$symbol}' ";
        $sql .= "AND timestamp >= '{$startTime}' AND timestamp <= '{$endTime}' ";
        $sql .= "ORDER BY timestamp ASC";

        try {
            $result = $this->client->select($sql);

            $data = [];

            foreach ($result as $row) {
                $data[] = [
                    'timestamp' => (int) $row['timestamp'],
                    'value' => (float) $row['value'],
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
     * @param string $symbol
     * @param int $limit
     * @return array
     */
    public function latest(string $symbol, int $limit = 10): array
    {
        $sql = "SELECT timestamp, value FROM {$this->tableName} ";
        $sql .= "WHERE symbol = '{$symbol}' ";
        $sql .= "ORDER BY timestamp DESC ";
        $sql .= "LIMIT {$limit}";

        try {
            $result = $this->client->select($sql);

            $data = [];

            foreach ($result as $row) {
                $data[] = [
                    'timestamp' => (int) $row['timestamp'],
                    'value' => (float) $row['value'],
                ];
            }

            return array_reverse($data);
        } catch (\Exception $e) {
            return [];
        }
    }
}
