<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Firestore.
 * Demonstrates "Insert with auto-generated ID" in Firestore style.
 */
final class FirestoreDocumentRepository
{
    private \Google\Cloud\Firestore\FirestoreClient $client;
    private string $collection;

    public function __construct(\Google\Cloud\Firestore\FirestoreClient $client, string $collection = 'documents')
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
        $collection = $this->client->collection($this->collection);
        $data['createdAt'] = new \Google\Cloud\Firestore\Timestamp(time());
        $data['updatedAt'] = new \Google\Cloud\Firestore\Timestamp(time());

        $docRef = $collection->add($data);

        return $docRef->id();
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
        $docRef = $collection->document($id);

        $snapshot = $docRef->snapshot();

        if (!$snapshot->exists()) {
            return null;
        }

        return $snapshot->data();
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
        $collection = $this->client->collection($this->collection);
        $docRef = $collection->document($id);
        $data['updatedAt'] = new \Google\Cloud\Firestore\Timestamp(time());

        try {
            $docRef->set($data, ['merge' => true]);
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
        $docRef = $collection->document($id);

        try {
            $docRef->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
