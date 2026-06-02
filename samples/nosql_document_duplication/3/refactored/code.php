<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Optimistic locking mixin for document repositories.
 */
trait OptimisticLockingTrait
{
    /**
     * Current version of the document.
     */
    protected int $currentVersion = 0;

    /**
     * Get current version.
     *
     * @return int
     */
    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }

    /**
     * Set current version.
     *
     * @param int $version
     * @return void
     */
    public function setCurrentVersion(int $version): void
    {
        $this->currentVersion = $version;
    }

    /**
     * Validate version matches expected.
     *
     * @param int $expectedVersion
     * @return void
     * @throws \RuntimeException
     */
    protected function validateVersion(int $expectedVersion): void
    {
        if ($this->currentVersion !== $expectedVersion) {
            throw new \RuntimeException(
                'Version conflict: expected ' . $expectedVersion .
                ', current ' . $this->currentVersion
            );
        }
    }

    /**
     * Increment version after successful update.
     *
     * @return void
     */
    protected function incrementVersion(): void
    {
        $this->currentVersion++;
    }
}

/**
 * Interface for optimistic locking repositories.
 */
interface OptimisticLockRepositoryInterface
{
    /**
     * Update with optimistic locking.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool;

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array;

    /**
     * Insert document with version.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string;
}

/**
 * Abstract base class for optimistic locking repositories.
 */
abstract class AbstractOptimisticLockRepository implements OptimisticLockRepositoryInterface
{
    use OptimisticLockingTrait;

    /**
     * {@inheritdoc}
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $this->loadVersion($id, $expectedVersion);
        $this->validateVersion($expectedVersion);

        $data = $this->prepareUpdate($id, $data, $expectedVersion);

        return $this->executeUpdate($id, $data, $expectedVersion);
    }

    /**
     * Load version from storage.
     *
     * @param string $id
     * @param int $expectedVersion
     * @return void
     */
    protected function loadVersion(string $id, int $expectedVersion): void
    {
        $document = $this->findById($id);

        if ($document === null) {
            throw new \RuntimeException('Document not found');
        }

        $this->currentVersion = $document['version'] ?? 0;
    }

    /**
     * Prepare update data.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return array
     */
    protected function prepareUpdate(string $id, array $data, int $expectedVersion): array
    {
        $data['version'] = $expectedVersion + 1;
        $data['updatedAt'] = $this->getCurrentTimestamp();

        return $data;
    }

    /**
     * Execute update operation.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     */
    abstract protected function executeUpdate(string $id, array $data, int $expectedVersion): bool;

    /**
     * Get current timestamp.
     *
     * @return int|string
     */
    abstract protected function getCurrentTimestamp(): int|string;
}

/**
 * Factory for optimistic lock repositories.
 */
final class OptimisticLockRepositoryFactory
{
    public static function create(string $type, array $config = []): OptimisticLockRepositoryInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoOptimisticLockRepository(
                $config['database']
            ),
            'couchbase' => new \App\Database\NoSQL\CouchbaseOptimisticLockRepository(
                $config['client']
            ),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchOptimisticLockRepository(
                $config['client']
            ),
            'dynamodb' => new \App\Database\NoSQL\DynamoDBOptimisticLockRepository(
                $config['client']
            ),
            'arangodb' => new \App\Database\NoSQL\ArangoDBOptimisticLockRepository(
                $config['client']
            ),
            'firestore' => new \App\Database\NoSQL\FirestoreOptimisticLockRepository(
                $config['client']
            ),
            'cosmosdb' => new \App\Database\NoSQL\CosmosDBOptimisticLockRepository(
                $config['client']
            ),
            'couchdb' => new \App\Database\NoSQL\CouchDBOptimisticLockRepository(
                $config['client']
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
