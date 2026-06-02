<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Couchbase.
 * Demonstrates "Insert with auto-generated ID" in Couchbase style.
 */
final class CouchbaseDocumentRepository
{
    private \Couchbase\Client $client;
    private string $bucket;

    public function __construct(\Couchbase\Client $client, string $bucket = 'documents')
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    /**
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        $documentId = uniqid('doc_', true);
        $data['createdAt'] = time();
        $data['updatedAt'] = time();

        try {
            $result = $collection->insert($documentId, $data);
            return $documentId;
        } catch (\Couchbase\Exception\CouchbaseException $e) {
            throw new \RuntimeException(
                'Failed to insert document: ' . $e->getMessage(),
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
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        try {
            $result = $collection->get($id);
            return $result->content();
        } catch (\Couchbase\Exception\DocumentNotFoundException $e) {
            return null;
        }
    }

    /**
     * Update document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        $data['updatedAt'] = time();

        try {
            $result = $collection->replace($id, $data);
            return $result->mutationToken() !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upsert document (insert or update).
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function upsert(string $id, array $data): bool
    {
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        $data['updatedAt'] = time();

        try {
            $collection->upsert($id, $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
