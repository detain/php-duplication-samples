<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Firestore.
 * Demonstrates "Update with optimistic locking" in Firestore style.
 */
final class FirestoreOptimisticLockRepository
{
    private \Google\Cloud\Firestore\FirestoreClient $client;
    private string $collection;

    public function __construct(\Google\Cloud\Firestore\FirestoreClient $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Update with optimistic locking using transaction.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $db = $this->client->database();
        $collection = $db->collection($this->collection);
        $docRef = $collection->document($id);

        return $db->runTransaction(function ($transaction) use ($docRef, $id, $data, $expectedVersion) {
            $snapshot = $transaction->snapshot($docRef);

            if (!$snapshot->exists()) {
                throw new \RuntimeException('Document not found');
            }

            $currentData = $snapshot->data();
            $currentVersion = $currentData['version'] ?? 0;

            if ($currentVersion !== $expectedVersion) {
                throw new \RuntimeException(
                    'Version conflict: expected ' . $expectedVersion .
                    ', current ' . $currentVersion
                );
            }

            $data['version'] = $expectedVersion + 1;
            $data['updatedAt'] = new \Google\Cloud\Firestore\Timestamp(time());

            $transaction->update($docRef, $data);

            return true;
        });
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
     * Insert document with version.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $collection = $this->client->collection($this->collection);
        $data['version'] = 1;
        $data['createdAt'] = new \Google\Cloud\Firestore\Timestamp(time());
        $data['updatedAt'] = new \Google\Cloud\Firestore\Timestamp(time());

        $docRef = $collection->add($data);

        return $docRef->id();
    }
}
