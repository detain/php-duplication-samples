<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Search with WHERE clause" in Elasticsearch style.
 */
final class ElasticsearchSearchRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Search documents with WHERE clause.
     *
     * @param array $conditions
     * @param array $options
     * @return array
     */
    public function search(array $conditions, array $options = []): array
    {
        $query = $this->buildQuery($conditions);

        $params = [
            'index' => $this->index,
            'body' => [
                'query' => $query,
            ],
        ];

        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }

        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }

        if (isset($options['sort'])) {
            $params['body']['sort'] = $options['sort'];
        }

        $response = $this->client->search($params);

        $results = [];
        foreach ($response['hits']['hits'] as $hit) {
            $results[] = $hit['_source'];
        }

        return $results;
    }

    /**
     * Search with pagination.
     *
     * @param array $conditions
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchPaginated(array $conditions, int $page = 1, int $perPage = 20): array
    {
        $query = $this->buildQuery($conditions);

        $response = $this->client->search([
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'sort' => $conditions['sort'] ?? ['createdAt' => ['order' => 'desc']],
            ],
        ]);

        $total = $response['hits']['total']['value'] ?? 0;
        $results = [];

        foreach ($response['hits']['hits'] as $hit) {
            $results[] = $hit['_source'];
        }

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    private function buildQuery(array $conditions): array
    {
        $must = [];

        foreach ($conditions as $field => $condition) {
            if ($field === 'sort') {
                continue;
            }

            if (is_array($condition) && isset($condition['operator'])) {
                $operator = $condition['operator'];
                $value = $condition['value'];

                $must[] = match ($operator) {
                    'eq' => ['term' => [$field => $value]],
                    'ne' => ['bool' => ['must_not' => ['term' => [$field => $value]]]],
                    'gt' => ['range' => [$field => ['gt' => $value]]],
                    'gte' => ['range' => [$field => ['gte' => $value]]],
                    'lt' => ['range' => [$field => ['lt' => $value]]],
                    'lte' => ['range' => [$field => ['lte' => $value]]],
                    'in' => ['terms' => [$field => $value]],
                    'like' => ['wildcard' => [$field => '*' . $value . '*']],
                    default => ['term' => [$field => $value]],
                };
            } elseif (is_array($condition)) {
                $must[] = ['terms' => [$field => $condition]];
            } else {
                $must[] = ['term' => [$field => $condition]];
            }
        }

        if (empty($must)) {
            return ['match_all' => new \stdClass()];
        }

        return ['bool' => ['must' => $must]];
    }
}
