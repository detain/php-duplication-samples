<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CouchDB.
 * Demonstrates "Find document by ID" operation in CouchDB style.
 */
final class CouchDBDocumentRepository
{
    private \CouchDB\Client $client;
    private string $database;

    public function __construct(\CouchDB\Client $client, string $database = 'documents')
    {
        $this->client = $client;
        $this->database = $database;
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
            $document = $this->client->get($this->database, $id);

            if ($document === null) {
                return null;
            }

            return $document;
        } catch (\CouchDB\Exception\NotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to find document: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find documents by selector.
     *
     * @param array $selector
     * @param array $options
     * @return array
     */
    public function find(array $selector, array $options = []): array
    {
        try {
            $query = array_merge([
                'selector' => $selector,
            ], $options);

            $response = $this->client->post($this->database . '/_find', $query);

            return $response['docs'] ?? [];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to find documents: ' . $e->getMessage(),
                0,
                $e
            );
        }
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

        try {
            $response = $this->client->put($this->database . '/' . uniqid(), $data);

            return $response['id'];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to insert document: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Update a document.
     *
     * @param string $id
     * @param array $data
     * @param string $rev
     * @return bool
     */
    public function update(string $id, array $data, string $rev): bool
    {
        $data['updatedAt'] = date('Y-m-d H:i:s');

        try {
            $response = $this->client->put($this->database . '/' . $id, $data, [
                'rev' => $rev,
            ]);

            return isset($response['ok']) && $response['ok'];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to update document: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Delete a document.
     *
     * @param string $id
     * @param string $rev
     * @return bool
     */
    public function delete(string $id, string $rev): bool
    {
        try {
            $response = $this->client->delete($this->database . '/' . $id, [
                'rev' => $rev,
            ]);

            return isset($response['ok']) && $response['ok'];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to delete document: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
