<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for batch user operations.
 */
interface BatchUserRepositoryInterface
{
    /**
     * Batch insert users efficiently.
     *
     * @param array<array{name: string, email: string, password: string, status?: string}> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array<UserDTO>
     */
    public function batchInsertUsers(
        array $users,
        int $batchSize = 100,
        ?callable $progressCallback = null
    ): array;

    /**
     * Bulk upsert users.
     *
     * @param array<array{id?: int, name: string, email: string, password: string}> $users
     * @return array<UserDTO>
     */
    public function bulkUpsertUsers(array $users): array;

    /**
     * Import users from CSV data.
     *
     * @param array<array{0: string, 1: string, 2: string}> $csvData
     * @param int $batchSize
     * @return ImportResultDTO
     */
    public function importUsersFromCsv(array $csvData, int $batchSize = 100): ImportResultDTO;
}

/**
 * Import result data transfer object.
 */
final readonly class ImportResultDTO
{
    public function __construct(
        public int $imported,
        public int $failed,
        public array $errors,
    ) {}

    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'failed' => $this->failed,
            'errors' => $this->errors,
        ];
    }

    public function hasErrors(): bool
    {
        return $this->failed > 0;
    }
}

/**
 * Batch operation result data transfer object.
 */
final readonly class BatchOperationResultDTO
{
    public function __construct(
        public array $users,
        public int $processed,
        public int $total,
        public float $executionTimeMs,
    ) {}

    public function toArray(): array
    {
        return [
            'users' => array_map(fn(UserDTO $u) => $u->toArray(), $this->users),
            'processed' => $this->processed,
            'total' => $this->total,
            'executionTimeMs' => $this->executionTimeMs,
        ];
    }
}

/**
 * Abstract base batch user repository.
 */
abstract class AbstractBatchUserRepository implements BatchUserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function batchInsertUsers(
        array $users,
        int $batchSize = 100,
        ?callable $progressCallback = null
    ): array {
        $startTime = microtime(true);

        $result = $this->doBatchInsert($users, $batchSize, $progressCallback);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return array_map(
            fn($user) => $this->mapToDTO($user),
            $result
        );
    }

    /**
     * {@inheritdoc}
     */
    public function bulkUpsertUsers(array $users): array
    {
        $startTime = microtime(true);

        $result = $this->doBulkUpsert($users);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return array_map(
            fn($user) => $this->mapToDTO($user),
            $result
        );
    }

    /**
     * {@inheritdoc}
     */
    public function importUsersFromCsv(array $csvData, int $batchSize = 100): ImportResultDTO
    {
        $startTime = microtime(true);

        $result = $this->doImportFromCsv($csvData, $batchSize);

        $executionTime = (microtime(true) - $startTime) * 1000;

        return new ImportResultDTO(
            imported: $result['imported'],
            failed: $result['failed'],
            errors: $result['errors'],
        );
    }

    /**
     * Perform batch insert.
     *
     * @param array<array<string, mixed>> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array
     */
    abstract protected function doBatchInsert(
        array $users,
        int $batchSize,
        ?callable $progressCallback
    ): array;

    /**
     * Perform bulk upsert.
     *
     * @param array<array<string, mixed>> $users
     * @return array
     */
    abstract protected function doBulkUpsert(array $users): array;

    /**
     * Perform CSV import.
     *
     * @param array<array{0: string, 1: string, 2: string}> $csvData
     * @param int $batchSize
     * @return array{imported: int, failed: int, errors: array<string>}
     */
    abstract protected function doImportFromCsv(array $csvData, int $batchSize): array;

    /**
     * Map user entity to DTO.
     *
     * @param mixed $user
     * @return UserDTO
     */
    abstract protected function mapToDTO(mixed $user): UserDTO;
}

/**
 * Factory for creating batch user repositories.
 */
final class BatchUserRepositoryFactory
{
    public static function create(string $type, array $config = []): BatchUserRepositoryInterface
    {
        return match ($type) {
            'doctrine' => new \App\Database\Doctrine\DoctrineUserRepository(
                $config['entity_manager']
            ),
            'eloquent' => new \App\Database\Eloquent\EloquentUserRepository(),
            'propel' => new \App\Database\Propel\PropelUserRepository(
                $config['connection'] ?? null
            ),
            'cycle' => new \App\Database\Cycle\CycleUserRepository(
                $config['entity_manager']
            ),
            'yii' => new \App\Database\Yii\YiiUserRepository(),
            'cake' => new \App\Database\Cake\CakeUserRepository(
                $config['table']
            ),
            'redbean' => new \App\Database\RedBean\RedBeanUserRepository(
                $config['database'] ?? null
            ),
            default => throw new \RuntimeException("Unknown repository type: {$type}"),
        };
    }
}
