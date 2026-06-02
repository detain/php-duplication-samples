<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Update with optimistic locking" in MongoDB style.
 */
final class MongoOptimisticLockRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
    }

    /**
     * Update with optimistic locking using version field.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $data['updatedAt'] = new \MongoDB\BSON\UTCDateTime();
        $data['version'] = $expectedVersion + 1;

        $result = $collection->updateOne(
            [
                '_id' => new \MongoDB\BSON\ObjectId($id),
                'version' => $expectedVersion,
            ],
            ['$set' => $data]
        );

        if ($result->getMatchedCount() === 0) {
            $current = $this->findById($id);
            if ($current === null) {
                throw new \RuntimeException('Document not found');
            }
            throw new \RuntimeException(
                'Version conflict: expected ' . $expectedVersion .
                ', found ' . ($current['version'] ?? 'unknown')
            );
        }

        return $result->getModifiedCount() > 0;
    }

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array
    {
        $collection = $this->database->selectCollection($this->collectionName);
        $result = $collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

        if ($result === null) {
            return null;
        }

        return $this->bsonToArray($result);
    }

    /**
     * Insert document with version.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $data['version'] = 1;
        $data['createdAt'] = new \MongoDB\BSON\UTCDateTime();
        $data['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        $result = $collection->insertOne($data);

        return (string) $result->getInsertedId();
    }

    private function bsonToArray($bson): array
    {
        $array = (array) $bson;
        $result = [];
        foreach ($array as $key => $value) {
            if ($value instanceof \MongoDB\BSON\ObjectId) {
                $result['_id'] = (string) $value;
            } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $result[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
