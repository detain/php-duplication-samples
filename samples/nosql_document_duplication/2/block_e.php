<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "Insert with auto-generated ID" in ArangoDB style.
 */
final class ArangoDBDocumentRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $data['createdAt'] = time();
        $data['updatedAt'] = time();

        $collection = $this->client->collection($this->collection);
        $result = $collection->save($data);

        return $result['_id'];
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
     * Update document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $data['updatedAt'] = time();

        $collection = $this->client->collection($this->collection);

        try {
            $collection->update($id, $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Replace document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function replace(string $id, array $data): bool
    {
        $data['updatedAt'] = time();

        $collection = $this->client->collection($this->collection);

        try {
            $collection->replace($id, $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $collection = $this->client->collection($this->collection);

        try {
            $collection->remove($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
