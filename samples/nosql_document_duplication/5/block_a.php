<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Search with WHERE clause" in MongoDB style.
 */
final class MongoSearchRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
    }

    /**
     * Search documents with WHERE clause.
     *
     * @param array $conditions
     * @param array $options
     * @return array
     */
    public function search(array $conditions, array $options = []): array
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $query = $this->buildQuery($conditions);
        $cursor = $collection->find($query, $options);

        $results = [];
        foreach ($cursor as $document) {
            $results[] = $this->bsonToArray($document);
        }

        return $results;
    }

    /**
     * Search with pagination.
     *
     * @param array $conditions
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchPaginated(array $conditions, int $page = 1, int $perPage = 20): array
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $query = $this->buildQuery($conditions);
        $total = $collection->countDocuments($query);

        $options = [
            'skip' => ($page - 1) * $perPage,
            'limit' => $perPage,
            'sort' => $conditions['sort'] ?? ['createdAt' => -1],
        ];

        unset($conditions['sort']);
        $query = $this->buildQuery($conditions);

        $cursor = $collection->find($query, $options);

        $results = [];
        foreach ($cursor as $document) {
            $results[] = $this->bsonToArray($document);
        }

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    private function buildQuery(array $conditions): array
    {
        $query = [];

        foreach ($conditions as $field => $condition) {
            if (is_array($condition) && isset($condition['operator'])) {
                $operator = '$' . $condition['operator'];
                $query[$field] = [$operator => $condition['value']];
            } elseif (is_array($condition)) {
                $query[$field] = ['$in' => $condition];
            } else {
                $query[$field] = $condition;
            }
        }

        return $query;
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
