<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Refactored document repository with abstraction layer.
 * Demonstrates how to eliminate duplication across NoSQL implementations.
 */
interface AutoIdDocumentRepositoryInterface
{
    /**
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string;

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array;

    /**
     * Update document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool;

    /**
     * Delete document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}

/**
 * Abstract base class for auto-ID document repositories.
 */
abstract class AbstractAutoIdRepository implements AutoIdDocumentRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function insert(array $data): string
    {
        $this->beforeInsert($data);
        $id = $this->doInsert($data);
        $this->afterInsert($id, $data);
        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $id, array $data): bool
    {
        if (!$this->exists($id)) {
            return false;
        }
        $this->beforeUpdate($id, $data);
        $result = $this->doUpdate($id, $data);
        $this->afterUpdate($id, $data);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $id): bool
    {
        if (!$this->exists($id)) {
            return false;
        }
        $this->beforeDelete($id);
        $result = $this->doDelete($id);
        $this->afterDelete($id);
        return $result;
    }

    /**
     * Check if document exists.
     *
     * @param string $id
     * @return bool
     */
    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Hook before insert.
     *
     * @param array $data
     */
    protected function beforeInsert(array &$data): void
    {
        $data['createdAt'] = $this->getCurrentTimestamp();
        $data['updatedAt'] = $this->getCurrentTimestamp();
    }

    /**
     * Hook after insert.
     *
     * @param string $id
     * @param array $data
     */
    protected function afterInsert(string $id, array $data): void
    {
    }

    /**
     * Hook before update.
     *
     * @param string $id
     * @param array $data
     */
    protected function beforeUpdate(string $id, array &$data): void
    {
        $data['updatedAt'] = $this->getCurrentTimestamp();
    }

    /**
     * Hook after update.
     *
     * @param string $id
     * @param array $data
     */
    protected function afterUpdate(string $id, array $data): void
    {
    }

    /**
     * Hook before delete.
     *
     * @param string $id
     */
    protected function beforeDelete(string $id): void
    {
    }

    /**
     * Hook after delete.
     *
     * @param string $id
     */
    protected function afterDelete(string $id): void
    {
    }

    /**
     * Get current timestamp in appropriate format.
     *
     * @return int|string
     */
    abstract protected function getCurrentTimestamp(): int|string;

    /**
     * Perform insert operation.
     *
     * @param array $data
     * @return string
     */
    abstract protected function doInsert(array $data): string;

    /**
     * Perform update operation.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    abstract protected function doUpdate(string $id, array $data): bool;

    /**
     * Perform delete operation.
     *
     * @param string $id
     * @return bool
     */
    abstract protected function doDelete(string $id): bool;
}

/**
 * Factory for creating auto-ID document repositories.
 */
final class AutoIdRepositoryFactory
{
    public static function create(string $type, array $config = []): AutoIdDocumentRepositoryInterface
    {
        return match ($type) {
            'couchbase' => new \App\Database\NoSQL\CouchbaseDocumentRepository(
                $config['client'],
                $config['bucket'] ?? 'default'
            ),
            'dynamodb' => new \App\Database\NoSQL\DynamoDBDocumentRepository(
                $config['client'],
                $config['table'] ?? 'documents'
            ),
            'ravendb' => new \App\Database\NoSQL\RavenDBDocumentRepository(
                $config['client'],
                $config['database'] ?? 'documents'
            ),
            'solr' => new \App\Database\NoSQL\SolrDocumentRepository(
                $config['client'],
                $config['core'] ?? 'documents'
            ),
            'arangodb' => new \App\Database\NoSQL\ArangoDBDocumentRepository(
                $config['client'],
                $config['collection'] ?? 'documents'
            ),
            'marklogic' => new \App\Database\NoSQL\MarkLogicDocumentRepository(
                $config['client']
            ),
            'cosmosdb' => new \App\Database\NoSQL\CosmosDBDocumentRepository(
                $config['client'],
                $config['database'] ?? 'mydb',
                $config['collection'] ?? 'documents'
            ),
            'firestore' => new \App\Database\NoSQL\FirestoreDocumentRepository(
                $config['client'],
                $config['collection'] ?? 'documents'
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
