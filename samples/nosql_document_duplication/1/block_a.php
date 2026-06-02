<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Find document by ID" operation in MongoDB style.
 */
final class MongoDocumentRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
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
     * Find documents by criteria.
     *
     * @param array $criteria
     * @param array $options
     * @return array
     */
    public function find(array $criteria, array $options = []): array
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $cursor = $collection->find($criteria, $options);

        $results = [];
        foreach ($cursor as $document) {
            $results[] = $this->bsonToArray($document);
        }

        return $results;
    }

    /**
     * Insert a document.
     *
     * @param array $data
     * @return string Inserted ID
     */
    public function insert(array $data): string
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $data['createdAt'] = new \MongoDB\BSON\UTCDateTime();
        $data['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        $result = $collection->insertOne($data);

        return (string) $result->getInsertedId();
    }

    /**
     * Update a document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $data['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        $result = $collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($id)],
            ['$set' => $data]
        );

        return $result->getModifiedCount() > 0;
    }

    /**
     * Delete a document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $result = $collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);

        return $result->getDeletedCount() > 0;
    }

    private function bsonToArray($bson): array
    {
        if ($bson instanceof \MongoDB\BSON\Document) {
            $array = (array) $bson;
        } else {
            $array = (array) $bson;
        }

        $result = [];
        foreach ($array as $key => $value) {
            if ($value instanceof \MongoDB\BSON\ObjectId) {
                $result['_id'] = (string) $value;
            } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
                $result[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
            } elseif ($value instanceof \MongoDB\BSON\Document || $value instanceof \MongoDB\BSON\ArrayItera) {
                $result[$key] = $this->bsonToArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
