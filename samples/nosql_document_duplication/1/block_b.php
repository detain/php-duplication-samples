<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Find document by ID" operation in Elasticsearch style.
 */
final class ElasticsearchDocumentRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array
    {
        try {
            $response = $this->client->get([
                'index' => $this->index,
                'id' => $id,
            ]);

            if (!$response['found']) {
                return null;
            }

            return [
                '_id' => $response['_id'],
                '_source' => $response['_source'],
            ];
        } catch (\Elastic\Elasticsearch\Exception\ClientResponseException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Find documents by query.
     *
     * @param array $query
     * @param array $options
     * @return array
     */
    public function find(array $query, array $options = []): array
    {
        $params = array_merge([
            'index' => $this->index,
            'body' => ['query' => $query],
        ], $options);

        $response = $this->client->search($params);

        $results = [];
        foreach ($response['hits']['hits'] as $hit) {
            $results[] = [
                '_id' => $hit['_id'],
                '_score' => $hit['_score'],
                '_source' => $hit['_source'],
            ];
        }

        return $results;
    }

    /**
     * Insert a document.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = date('Y-m-d H:i:s');

        $response = $this->client->index([
            'index' => $this->index,
            'body' => $data,
        ]);

        return $response['_id'];
    }

    /**
     * Update a document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $data['updatedAt'] = date('Y-m-d H:i:s');

        try {
            $response = $this->client->update([
                'index' => $this->index,
                'id' => $id,
                'body' => [
                    'doc' => $data,
                ],
            ]);

            return $response['result'] === 'updated';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        try {
            $response = $this->client->delete([
                'index' => $this->index,
                'id' => $id,
            ]);

            return $response['result'] === 'deleted';
        } catch (\Exception $e) {
            return false;
        }
    }
}
