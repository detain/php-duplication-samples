<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Firestore.
 * Demonstrates "Delete with cascade" in Firestore style.
 */
final class FirestoreCascadeDeleteRepository
{
    private \Google\Cloud\Firestore\FirestoreClient $client;
    private string $collection;

    public function __construct(\Google\Cloud\Firestore\FirestoreClient $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Delete document with cascade to related documents.
     *
     * @param string $id
     * @param array $cascadeRules Rules for cascade deletion
     * @return bool
     */
    public function deleteWithCascade(string $id, array $cascadeRules = []): bool
    {
        $db = $this->client->database();
        $batch = $db->batch();
        $collection = $db->collection($this->collection);
        $docRef = $collection->document($id);

        $snapshot = $docRef->snapshot();

        if (!$snapshot->exists()) {
            return false;
        }

        $document = $snapshot->data();

        foreach ($cascadeRules as $rule) {
            $targetCollection = $db->collection($rule['collection']);
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                if ($rule['cascade'] === 'delete') {
                    $query = $targetCollection->where($targetField, '=', $targetValue);
                    $documents = $query->documents();

                    foreach ($documents as $doc) {
                        $batch->delete($doc->reference());
                    }
                } elseif ($rule['cascade'] === 'nullify') {
                    $query = $targetCollection->where($targetField, '=', $targetValue);
                    $documents = $query->documents();

                    foreach ($documents as $doc) {
                        $batch->update($doc->reference(), [$targetField => null]);
                    }
                }
            }
        }

        $batch->delete($docRef);
        $batch->commit();

        return true;
    }

    /**
     * Find document by ID.
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
}
