<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CosmosDB.
 * Demonstrates "Update with optimistic locking" in CosmosDB style.
 */
final class CosmosDBOptimisticLockRepository
{
    private \CosmosDB\Client $client;
    private string $database;
    private string $collection;

    public function __construct(
        \CosmosDB\Client $client,
        string $database = 'mydb',
        string $collection = 'documents'
    ) {
        $this->client = $client;
        $this->database = $database;
        $this->collection = $collection;
    }

    /**
     * Update with optimistic locking using etag.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $container = $this->client->getContainer($this->database, $this->collection);
        $data['version'] = $expectedVersion + 1;
        $data['_ts'] = time();

        try {
            $container->replaceDocument($id, $data);
            return true;
        } catch (\CosmosDB\Exception\PreconditionFailedException $e) {
            $current = $this->findById($id);
            throw new \RuntimeException(
                'Version conflict: expected ' . $expectedVersion .
                ', current ' . ($current['version'] ?? 'unknown')
            );
        } catch (\CosmosDB\Exception\NotFoundException $e) {
            throw new \RuntimeException('Document not found');
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
        $container = $this->client->getContainer($this->database, $this->collection);

        try {
            $result = $container->readDocument($id);
            return $result->getDecodedContent();
        } catch (\CosmosDB\Exception\NotFoundException $e) {
            return null;
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
        $documentId = $this->generateUuid();
        $data['id'] = $documentId;
        $data['version'] = 1;
        $data['_ts'] = time();

        $container = $this->client->getContainer($this->database, $this->collection);
        $result = $container->createDocument($data);

        return $result->getId();
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
