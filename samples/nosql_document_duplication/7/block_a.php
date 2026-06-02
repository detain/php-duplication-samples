<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Batch insert" in MongoDB style.
 */
final class MongoBatchInsertRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
    }

    /**
     * Batch insert multiple documents.
     *
     * @param array $documents
     * @return array Inserted IDs
     */
    public function batchInsert(array $documents): array
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $now = new \MongoDB\BSON\UTCDateTime();

        foreach ($documents as &$document) {
            unset($document['_id']);
            $document['createdAt'] = $now;
            $document['updatedAt'] = $now;
        }

        $result = $collection->insertMany($documents, ['ordered' => false]);

        $insertedIds = [];
        foreach ($result->getInsertedIds() as $id) {
            $insertedIds[] = (string) $id;
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
        $collection = $this->database->selectCollection($this->collectionName);

        $operations = [];
        $now = new \MongoDB\BSON\UTCDateTime();

        foreach ($documents as $document) {
            $id = $document[$idField] ?? null;

            if ($id === null) {
                continue;
            }

            unset($document['_id']);

            $operations[] = [
                'updateOne' => [
                    [$idField => $id],
                    [
                        '$set' => array_merge($document, [
                            'updatedAt' => $now,
                        ]),
                        '$setOnInsert' => [
                            'createdAt' => $now,
                        ],
                    ],
                    ['upsert' => true],
                ],
            ];
        }

        if (empty($operations)) {
            return 0;
        }

        $result = $collection->bulkWrite($operations);

        return $result->getModifiedCount() + $result->getUpsertedCount();
    }

    /**
     * Batch delete documents.
     *
     * @param array $ids
     * @return int Number of deleted
     */
    public function batchDelete(array $ids): int
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $objectIds = array_map(
            fn($id) => new \MongoDB\BSON\ObjectId($id),
            $ids
        );

        $result = $collection->deleteMany([
            '_id' => ['$in' => $objectIds],
        ]);

        return $result->getDeletedCount();
    }
}
