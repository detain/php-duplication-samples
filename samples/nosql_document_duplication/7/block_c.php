<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "Batch insert" in ArangoDB style.
 */
final class ArangoDBBatchInsertRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Batch insert multiple documents.
     *
     * @param array $documents
     * @return array Inserted IDs
     */
    public function batchInsert(array $documents): array
    {
        $collection = $this->client->collection($this->collection);

        $now = time();

        foreach ($documents as &$document) {
            unset($document['_id']);
            $document['createdAt'] = $now;
            $document['updatedAt'] = $now;
        }

        $result = $collection->insert($documents);

        $insertedIds = [];

        if (isset($result['documents'])) {
            foreach ($result['documents'] as $doc) {
                $insertedIds[] = $doc['_id'];
            }
        }

        return $insertedIds;
    }

    /**
     * Batch upsert documents.
     *
     * @param array $documents
     * @param string $idField
     * @return int Number of modified
     */
    public function batchUpsert(array $documents, string $idField = 'id'): int
    {
        $collection = $this->client->collection($this->collection);
        $now = time();
        $count = 0;

        foreach ($documents as $document) {
            $id = $document[$idField] ?? null;

            if ($id === null) {
                continue;
            }

            try {
                $existing = $collection->get($id);

                $updatedData = array_merge($document, [
                    'updatedAt' => $now,
                ]);

                unset($updatedData['_id']);

                $collection->update($id, $updatedData);
                $count++;
            } catch (\Exception $e) {
                $newData = array_merge($document, [
                    '_id' => $id,
                    'createdAt' => $now,
                    'updatedAt' => $now,
                ]);

                unset($newData[$idField]);

                $collection->save($newData);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Batch delete documents.
     *
     * @param array $ids
     * @return int Number of deleted
     */
    public function batchDelete(array $ids): int
    {
        $collection = $this->client->collection($this->collection);
        $count = 0;

        foreach ($ids as $id) {
            try {
                $collection->remove($id);
                $count++;
            } catch (\Exception $e) {
            }
        }

        return $count;
    }
}
