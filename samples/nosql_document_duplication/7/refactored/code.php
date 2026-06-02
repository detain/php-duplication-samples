<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Batch operation result.
 */
final readonly class BatchResult
{
    public function __construct(
        public array $insertedIds,
        public int $totalInserted,
        public int $totalFailed,
    ) {}

    public static function fromInsertedIds(array $ids): self
    {
        return new self(
            insertedIds: $ids,
            totalInserted: count($ids),
            totalFailed: 0,
        );
    }
}

/**
 * Interface for batch operations.
 */
interface BatchOperationsInterface
{
    /**
     * Batch insert documents.
     *
     * @param array $documents
     * @return BatchResult
     */
    public function batchInsert(array $documents): BatchResult;

    /**
     * Batch upsert documents.
     *
     * @param array $documents
     * @param string $idField
     * @return int
     */
    public function batchUpsert(array $documents, string $idField = 'id'): int;

    /**
     * Batch delete documents.
     *
     * @param array $ids
     * @return int
     */
    public function batchDelete(array $ids): int;
}

/**
 * Factory for batch operation repositories.
 */
final class BatchRepositoryFactory
{
    public static function create(string $type, array $config = []): BatchOperationsInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoBatchInsertRepository(
                $config['database']
            ),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchBatchInsertRepository(
                $config['client']
            ),
            'arangodb' => new \App\Database\NoSQL\ArangoDBBatchInsertRepository(
                $config['client']
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
