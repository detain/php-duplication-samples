<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Aggregation (count, sum, avg)" in Elasticsearch style.
 */
final class ElasticsearchAggregationRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
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

        $response = $this->client->count([
            'index' => $this->index,
            'body' => ['query' => $query],
        ]);

        return $response['count'] ?? 0;
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

        $response = $this->client->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'size' => 0,
                'aggs' => [
                    'total' => ['sum' => ['field' => $field]],
                ],
            ],
        ]);

        return $response['aggregations']['total']['value'] ?? 0.0;
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

        $response = $this->client->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'size' => 0,
                'aggs' => [
                    'average' => ['avg' => ['field' => $field]],
                ],
            ],
        ]);

        return $response['aggregations']['average']['value'] ?? 0.0;
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

        $response = $this->client->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'size' => 0,
                'aggs' => [
                    'stats' => ['extended_stats' => ['field' => $field]],
                ],
            ],
        ]);

        $stats = $response['aggregations']['stats'] ?? [];

        return [
            'count' => $stats['count'] ?? 0,
            'total' => $stats['sum'] ?? 0.0,
            'average' => $stats['avg'] ?? 0.0,
            'min' => $stats['min'] ?? 0.0,
            'max' => $stats['max'] ?? 0.0,
        ];
    }

    private function buildQuery(array $conditions): array
    {
        if (empty($conditions)) {
            return ['match_all' => new \stdClass()];
        }

        $must = [];

        foreach ($conditions as $field => $condition) {
            if (is_array($condition) && isset($condition['operator'])) {
                $operator = $condition['operator'];
                $value = $condition['value'];

                $must[] = match ($operator) {
                    'eq' => ['term' => [$field => $value]],
                    'gt' => ['range' => [$field => ['gt' => $value]]],
                    'gte' => ['range' => [$field => ['gte' => $value]]],
                    'lt' => ['range' => [$field => ['lt' => $value]]],
                    'lte' => ['range' => [$field => ['lte' => $value]]],
                    default => ['term' => [$field => $value]],
                };
            } else {
                $must[] = ['term' => [$field => $condition]];
            }
        }

        return ['bool' => ['must' => $must]];
    }
}
