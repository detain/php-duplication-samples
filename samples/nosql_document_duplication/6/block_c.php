<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "Aggregation (count, sum, avg)" in ArangoDB style.
 */
final class ArangoDBAggregationRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
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
        $aql = $this->buildAggregationAql($conditions, 'COUNT');
        $cursor = $this->client->query($aql['query'], $aql['bindVars']);

        return (int) ($cursor->first()['count'] ?? 0);
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
        $aql = $this->buildAggregationAql($conditions, 'SUM', $field);
        $cursor = $this->client->query($aql['query'], $aql['bindVars']);

        return (float) ($cursor->first()['total'] ?? 0.0);
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
        $aql = $this->buildAggregationAql($conditions, 'AVERAGE', $field);
        $cursor = $this->client->query($aql['query'], $aql['bindVars']);

        return (float) ($cursor->first()['average'] ?? 0.0);
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
        $collectionName = $this->collection;
        $where = [];
        $bindVars = [];
        $i = 1;

        foreach ($conditions as $fieldCond => $condition) {
            $bindKey = 'var' . $i++;
            $bindVars[$bindKey] = $condition['value'] ?? $condition;

            if (is_array($condition) && isset($condition['operator'])) {
                $where[] = "doc.{$fieldCond} {$condition['operator']} @{$bindKey}";
            } else {
                $where[] = "doc.{$fieldCond} == @{$bindKey}";
            }
        }

        $query = "FOR doc IN {$collectionName}";

        if (!empty($where)) {
            $query .= " FILTER " . implode(' AND ', $where);
        }

        $query .= " COLLECT AGGREGATE
            count = COUNT(),
            total = SUM(doc.{$field}),
            average = AVERAGE(doc.{$field}),
            min = MIN(doc.{$field}),
            max = MAX(doc.{$field})";

        $query .= " RETURN {count, total, average, min, max}";

        $cursor = $this->client->query($query, $bindVars);
        $result = $cursor->first();

        return $result ?? [
            'count' => 0,
            'total' => 0.0,
            'average' => 0.0,
            'min' => 0.0,
            'max' => 0.0,
        ];
    }

    private function buildAggregationAql(array $conditions, string $aggregation, ?string $field = null): array
    {
        $collectionName = $this->collection;
        $where = [];
        $bindVars = [];
        $i = 1;

        foreach ($conditions as $fieldCond => $condition) {
            $bindKey = 'var' . $i++;
            $bindVars[$bindKey] = $condition['value'] ?? $condition;

            if (is_array($condition) && isset($condition['operator'])) {
                $where[] = "doc.{$fieldCond} {$condition['operator']} @{$bindKey}";
            } else {
                $where[] = "doc.{$fieldCond} == @{$bindKey}";
            }
        }

        $query = "FOR doc IN {$collectionName}";

        if (!empty($where)) {
            $query .= " FILTER " . implode(' AND ', $where);
        }

        if ($aggregation === 'COUNT') {
            $query .= " COLLECT WITH COUNT INTO count RETURN {count}";
        } elseif ($field !== null) {
            $query .= " COLLECT AGGREGATE
                total = {$aggregation}(doc.{$field})";
            $query .= " RETURN {total}";
        }

        return ['query' => $query, 'bindVars' => $bindVars];
    }
}
