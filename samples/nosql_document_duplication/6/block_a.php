<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Aggregation (count, sum, avg)" in MongoDB style.
 */
final class MongoAggregationRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
    }

    /**
     * Count documents matching conditions.
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $query = $this->buildQuery($conditions);

        return $collection->countDocuments($query);
    }

    /**
     * Sum a field across matching documents.
     *
     * @param string $field
     * @param array $conditions
     * @return float
     */
    public function sum(string $field, array $conditions = []): float
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $query = $this->buildQuery($conditions);

        $pipeline = [
            ['$match' => $query],
            ['$group' => ['_id' => null, 'total' => ['$sum' => '$' . $field]]],
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        return $result[0]['total'] ?? 0.0;
    }

    /**
     * Average a field across matching documents.
     *
     * @param string $field
     * @param array $conditions
     * @return float
     */
    public function avg(string $field, array $conditions = []): float
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $query = $this->buildQuery($conditions);

        $pipeline = [
            ['$match' => $query],
            ['$group' => ['_id' => null, 'average' => ['$avg' => '$' . $field]]],
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        return $result[0]['average'] ?? 0.0;
    }

    /**
     * Get multiple aggregations at once.
     *
     * @param string $field
     * @param array $conditions
     * @return array
     */
    public function getStats(string $field, array $conditions = []): array
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $query = $this->buildQuery($conditions);

        $pipeline = [
            ['$match' => $query],
            ['$group' => [
                '_id' => null,
                'count' => ['$sum' => 1],
                'total' => ['$sum' => '$' . $field],
                'average' => ['$avg' => '$' . $field],
                'min' => ['$min' => '$' . $field],
                'max' => ['$max' => '$' . $field],
            ]],
        ];

        $result = $collection->aggregate($pipeline)->toArray();

        if (empty($result)) {
            return ['count' => 0, 'total' => 0.0, 'average' => 0.0, 'min' => 0.0, 'max' => 0.0];
        }

        return $result[0];
    }

    private function buildQuery(array $conditions): array
    {
        $query = [];

        foreach ($conditions as $field => $condition) {
            if (is_array($condition) && isset($condition['operator'])) {
                $operator = '$' . $condition['operator'];
                $query[$field] = [$operator => $condition['value']];
            } else {
                $query[$field] = $condition;
            }
        }

        return $query;
    }
}
