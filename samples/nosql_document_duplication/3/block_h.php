<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CouchDB.
 * Demonstrates "Update with optimistic locking" in CouchDB style.
 */
final class CouchDBOptimisticLockRepository
{
    private \CouchDB\Client $client;
    private string $database;

    public function __construct(\CouchDB\Client $client, string $database = 'documents')
    {
        $this->client = $client;
        $this->database = $database;
    }

    /**
     * Update with optimistic locking using revision ID.
     *
     * @param string $id
     * @param array $data
     * @param string $expectedRev
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, string $expectedRev): bool
    {
        $data['updatedAt'] = date('Y-m-d H:i:s');
        $data['version'] = $this->parseVersion($expectedRev) + 1;

        try {
            $response = $this->client->put($this->database . '/' . $id, $data, [
                'rev' => $expectedRev,
            ]);

            return isset($response['ok']) && $response['ok'];
        } catch (\CouchDB\Exception\ConflictException $e) {
            $current = $this->findById($id);
            throw new \RuntimeException(
                'Version conflict: document has been modified'
            );
        } catch (\Exception $e) {
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
     * Insert document with revision.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $data['version'] = 1;
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = date('Y-m-d H:i:s');

        try {
            $response = $this->client->post($this->database, $data);

            return $response['id'];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to insert document: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function parseVersion(string $rev): int
    {
        $parts = explode('-', $rev);
        return isset($parts[0]) ? (int) $parts[0] : 0;
    }
}
