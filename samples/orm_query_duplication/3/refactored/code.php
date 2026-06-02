<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for repositories with optimistic locking support.
 */
interface VersionedRepositoryInterface
{
    /**
     * Update entity with version checking.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $expectedVersion
     * @return VersionedDTO
     * @throws VersionConflictException
     */
    public function updateWithVersion(int $id, array $data, int $expectedVersion): VersionedDTO;

    /**
     * Update entity with automatic retry on conflict.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $maxRetries
     * @return VersionedDTO
     */
    public function updateWithRetry(int $id, array $data, int $maxRetries = 3): VersionedDTO;
}

/**
 * Exception for version conflicts.
 */
class VersionConflictException extends \RuntimeException
{
    private int $entityId;
    private int $expectedVersion;
    private int $actualVersion;

    public function __construct(
        int $entityId,
        int $expectedVersion,
        int $actualVersion,
        string $message = 'Version conflict detected'
    ) {
        parent::__construct($message, 409);
        $this->entityId = $entityId;
        $this->expectedVersion = $expectedVersion;
        $this->actualVersion = $actualVersion;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getExpectedVersion(): int
    {
        return $this->expectedVersion;
    }

    public function getActualVersion(): int
    {
        return $this->actualVersion;
    }
}

/**
 * DTO for versioned entities.
 */
final readonly class VersionedDTO
{
    public function __construct(
        public int $id,
        public int $version,
        public array $data = [],
        public ?\DateTimeInterface $updatedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'data' => $this->data,
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}

/**
 * Trait for versioned entity operations.
 */
trait VersionedEntityTrait
{
    protected int $version = 0;

    public function getVersion(): int
    {
        return $this->version;
    }

    public function incrementVersion(): void
    {
        $this->version++;
    }

    public function checkVersion(int $expected): void
    {
        if ($this->version !== $expected) {
            throw new VersionConflictException(
                $this->id ?? 0,
                $expected,
                $this->version
            );
        }
    }
}

/**
 * Abstract base repository with optimistic locking support.
 */
abstract class AbstractVersionedRepository implements VersionedRepositoryInterface
{
    /**
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $expectedVersion
     * @return VersionedDTO
     */
    public function updateWithVersion(int $id, array $data, int $expectedVersion): VersionedDTO
    {
        $entity = $this->findById($id);

        if ($entity === null) {
            throw new \RuntimeException("Entity with ID {$id} not found", 404);
        }

        $this->validateVersion($entity, $expectedVersion);

        $updated = $this->performUpdate($entity, $data);

        return $updated;
    }

    /**
     * @param int $id
     * @param array<string, mixed> $data
     * @param int $maxRetries
     * @return VersionedDTO
     */
    public function updateWithRetry(int $id, array $data, int $maxRetries = 3): VersionedDTO
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $entity = $this->findById($id);

                if ($entity === null) {
                    throw new \RuntimeException("Entity with ID {$id} not found", 404);
                }

                $version = $this->getVersion($entity);

                $this->validateVersion($entity, $version);

                $data['version'] = $version + 1;

                return $this->performUpdate($entity, $data);
            } catch (VersionConflictException $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw $e;
                }

                usleep(100000 * $attempts);
            }
        }

        throw new \RuntimeException('Update failed: max retries exceeded');
    }

    /**
     * Find entity by ID.
     *
     * @param int $id
     * @return mixed
     */
    abstract protected function findById(int $id): mixed;

    /**
     * Get entity version.
     *
     * @param mixed $entity
     * @return int
     */
    abstract protected function getVersion(mixed $entity): int;

    /**
     * Validate version matches.
     *
     * @param mixed $entity
     * @param int $expectedVersion
     * @throws VersionConflictException
     */
    abstract protected function validateVersion(mixed $entity, int $expectedVersion): void;

    /**
     * Perform the actual update.
     *
     * @param mixed $entity
     * @param array<string, mixed> $data
     * @return VersionedDTO
     */
    abstract protected function performUpdate(mixed $entity, array $data): VersionedDTO;
}

/**
 * Factory for creating versioned repositories.
 */
final class VersionedRepositoryFactory
{
    /**
     * @param string $type
     * @param array<string, mixed> $config
     * @return VersionedRepositoryInterface
     */
    public static function create(string $type, array $config = []): VersionedRepositoryInterface
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
