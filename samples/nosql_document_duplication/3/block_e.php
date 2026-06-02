<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "Update with optimistic locking" in ArangoDB style.
 */
final class ArangoDBOptimisticLockRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Update with optimistic locking using revision ID.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $collection = $this->client->collection($this->collection);
        $data['version'] = $expectedVersion + 1;
        $data['updatedAt'] = time();

        try {
            $collection->update($id, $data);
            return true;
        } catch (\ArangoDB\Client\Exceptions\PreconditionFailedException $e) {
            $current = $this->findById($id);
            throw new \RuntimeException(
                'Version conflict: expected ' . $expectedVersion .
                ', current ' . ($current['version'] ?? 'unknown')
            );
        } catch (\ArangoDB\Client\Exceptions\ClientException $e) {
            throw new \RuntimeException(
                'Update failed: ' . $e->getMessage(),
                0,
                $e
            );
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
        $collection = $this->client->collection($this->collection);

        try {
            $document = $collection->get($id);
            return $document->getAll();
        } catch (\ArangoDB\Client\Exceptions\ClientException $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
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
        $collection = $this->client->collection($this->collection);
        $data['version'] = 1;
        $data['createdAt'] = time();
        $data['updatedAt'] = time();

        $result = $collection->save($data);

        return $result['_id'];
    }
}
