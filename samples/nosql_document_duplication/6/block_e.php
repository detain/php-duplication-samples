<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Firestore.
 * Demonstrates "Aggregation (count, sum, avg)" in Firestore style.
 */
final class FirestoreAggregationRepository
{
    private \Google\Cloud\Firestore\FirestoreClient $client;
    private string $collection;

    public function __construct(\Google\Cloud\Firestore\FirestoreClient $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Count documents matching conditions.
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->buildQuery($conditions);

        $snapshot = $query->documents();

        $count = 0;
        foreach ($snapshot as $document) {
            $count++;
        }

        return $count;
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
        $query = $this->buildQuery($conditions);

        $snapshot = $query->documents();

        $total = 0.0;
        foreach ($snapshot as $document) {
            $data = $document->data();
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $total += (float) $data[$field];
            }
        }

        return $total;
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
        $query = $this->buildQuery($conditions);

        $snapshot = $query->documents();

        $total = 0.0;
        $count = 0;

        foreach ($snapshot as $document) {
            $data = $document->data();
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $total += (float) $data[$field];
                $count++;
            }
        }

        return $count > 0 ? $total / $count : 0.0;
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
        $query = $this->buildQuery($conditions);

        $snapshot = $query->documents();

        $count = 0;
        $total = 0.0;
        $min = PHP_FLOAT_MAX;
        $max = PHP_FLOAT_MIN;

        foreach ($snapshot as $document) {
            $data = $document->data();
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $value = (float) $data[$field];
                $total += $value;
                $min = min($min, $value);
                $max = max($max, $value);
                $count++;
            }
        }

        if ($count === 0) {
            return ['count' => 0, 'total' => 0.0, 'average' => 0.0, 'min' => 0.0, 'max' => 0.0];
        }

        return [
            'count' => $count,
            'total' => $total,
            'average' => $total / $count,
            'min' => $min,
            'max' => $max,
        ];
    }

    private function buildQuery(array $conditions)
    {
        $collection = $this->client->collection($this->collection);
        $query = $collection;

        foreach ($conditions as $field => $condition) {
            if (is_array($condition) && isset($condition['operator'])) {
                $operator = $condition['operator'];
                $value = $condition['value'];

                $query = match ($operator) {
                    'eq' => $query->where($field, '=', $value),
                    'gt' => $query->where($field, '>', $value),
                    'gte' => $query->where($field, '>=', $value),
                    'lt' => $query->where($field, '<', $value),
                    'lte' => $query->where($field, '<=', $value),
                    default => $query->where($field, '=', $value),
                };
            } else {
                $query = $query->where($field, '=', $condition);
            }
        }

        return $query;
    }
}
