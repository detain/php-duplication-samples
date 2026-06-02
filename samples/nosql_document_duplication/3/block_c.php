<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Update with optimistic locking" in Elasticsearch style.
 */
final class ElasticsearchOptimisticLockRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Update with optimistic locking using version field.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $data['version'] = $expectedVersion + 1;
        $data['updatedAt'] = date('Y-m-d H:i:s');

        try {
            $response = $this->client->update([
                'index' => $this->index,
                'id' => $id,
                'body' => [
                    'doc' => $data,
                    'version' => $expectedVersion,
                ],
            ]);

            return $response['result'] === 'updated';
        } catch (\Elastic\Elasticsearch\Exception\ClientResponseException $e) {
            if ($e->getCode() === 409) {
                $current = $this->findById($id);
                throw new \RuntimeException(
                    'Version conflict: expected ' . $expectedVersion .
                    ', current ' . ($current['version'] ?? 'unknown')
                );
            }
            throw $e;
        }
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

            return $response['_source'];
        } catch (\Elastic\Elasticsearch\Exception\ClientResponseException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Insert document with version.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $data['version'] = 1;
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = date('Y-m-d H:i:s');

        $response = $this->client->index([
            'index' => $this->index,
            'body' => $data,
        ]);

        return $response['_id'];
    }
}
