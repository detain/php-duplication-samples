<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Couchbase.
 * Demonstrates "Update with optimistic locking" in Couchbase style.
 */
final class CouchbaseOptimisticLockRepository
{
    private \Couchbase\Client $client;
    private string $bucket;

    public function __construct(\Couchbase\Client $client, string $bucket = 'documents')
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    /**
     * Update with optimistic locking using CAS value.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        $data['version'] = $expectedVersion + 1;
        $data['updatedAt'] = time();

        try {
            $result = $collection->replace($id, $data);

            if ($result->mutationToken() === null) {
                throw new \RuntimeException('Update failed');
            }

            return true;
        } catch (\Couchbase\Exception\DocumentNotFoundException $e) {
            throw new \RuntimeException('Document not found');
        } catch (\Couchbase\Exception\CasMismatchException $e) {
            throw new \RuntimeException(
                'Version conflict: document was modified by another process'
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
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        try {
            $result = $collection->get($id);
            return $result->content();
        } catch (\Couchbase\Exception\DocumentNotFoundException $e) {
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
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        $documentId = uniqid('doc_', true);
        $data['version'] = 1;
        $data['createdAt'] = time();
        $data['updatedAt'] = time();

        $collection->insert($documentId, $data);

        return $documentId;
    }

    /**
     * Get document with CAS value for locking.
     *
     * @param string $id
     * @return array|null
     */
    public function getWithCas(string $id): ?array
    {
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        try {
            $result = $collection->get($id);
            $content = $result->content();
            $content['_cas'] = $result->cas();

            return $content;
        } catch (\Couchbase\Exception\DocumentNotFoundException $e) {
            return null;
        }
    }
}
