<?php
declare(strict_types=1);

namespace App\Database;

use RuntimeException;

/**
 * Common interface for user repositories across different ORM implementations.
 * This abstraction allows the application to work with any ORM without
 * changing the calling code, enabling easy migration between ORMs or
 * supporting multiple ORMs in different contexts.
 */
interface UserRepositoryInterface
{
    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     * @return UserDTO|null The found user or null if not exists
     * @throws RuntimeException If query execution fails
     */
    public function findByEmail(string $email): ?UserDTO;

    /**
     * Find a user by email with eager loading of relations.
     *
     * @param string $email The email to search for
     * @param array<string> $relations Relations to eager load
     * @return UserDTO|null
     */
    public function findByEmailWith(string $email, array $relations = []): ?UserDTO;

    /**
     * Find a user by email or fail with an exception.
     *
     * @param string $email The email to search for
     * @return UserDTO
     * @throws RuntimeException If user not found
     */
    public function findByEmailOrFail(string $email): UserDTO;

    /**
     * Check if a user exists with the given email.
     *
     * @param string $email The email to check
     * @return bool True if user exists
     */
    public function existsByEmail(string $email): bool;

    /**
     * Find users by email domain.
     *
     * @param string $domain The email domain to search for
     * @return array<UserDTO>
     */
    public function findByEmailDomain(string $domain): array;
}

/**
 * Data Transfer Object representing a User.
 * This decouples the domain entity from the ORM-specific implementations.
 */
final readonly class UserDTO
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public ?\DateTimeInterface $createdAt = null,
        public array $relations = [],
    ) {}

    /**
     * Create a DTO from an array of data.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            createdAt: isset($data['created_at'])
                ? new \DateTime($data['created_at'])
                : null,
            relations: $data['relations'] ?? [],
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'relations' => $this->relations,
        ];
    }

    /**
     * Check if user has a specific relation loaded.
     *
     * @param string $relation
     * @return bool
     */
    public function hasRelation(string $relation): bool
    {
        return isset($this->relations[$relation]);
    }

    /**
     * Get a specific relation.
     *
     * @template T
     * @param string $relation
     * @return T|null
     */
    public function getRelation(string $relation): mixed
    {
        return $this->relations[$relation] ?? null;
    }
}

/**
 * Abstract base repository providing common functionality.
 */
abstract class AbstractUserRepository implements UserRepositoryInterface
{
    /**
     * Normalize email for case-insensitive comparison.
     *
     * @param string $email
     * @return string
     */
    protected function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Validate email format.
     *
     * @param string $email
     * @return void
     * @throws RuntimeException
     */
    protected function validateEmail(string $email): void
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email format: ' . $email);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?UserDTO
    {
        $this->validateEmail($email);
        $normalizedEmail = $this->normalizeEmail($email);

        return $this->doFindByEmail($normalizedEmail);
    }

    /**
     * Perform the actual find by email operation.
     * Subclasses must implement this method.
     *
     * @param string $normalizedEmail
     * @return UserDTO|null
     */
    abstract protected function doFindByEmail(string $normalizedEmail): ?UserDTO;

    /**
     * {@inheritdoc}
     */
    public function findByEmailWith(string $email, array $relations = []): ?UserDTO
    {
        $this->validateEmail($email);
        $normalizedEmail = $this->normalizeEmail($email);

        return $this->doFindByEmailWith($normalizedEmail, $relations);
    }

    /**
     * Perform the actual find by email with relations.
     *
     * @param string $normalizedEmail
     * @param array<string> $relations
     * @return UserDTO|null
     */
    abstract protected function doFindByEmailWith(string $normalizedEmail, array $relations): ?UserDTO;

    /**
     * {@inheritdoc}
     */
    public function findByEmailOrFail(string $email): UserDTO
    {
        $this->validateEmail($email);
        $normalizedEmail = $this->normalizeEmail($email);

        $user = $this->doFindByEmail($normalizedEmail);

        if ($user === null) {
            throw new RuntimeException("User with email '{$email}' not found");
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function existsByEmail(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        $normalizedEmail = $this->normalizeEmail($email);

        try {
            return $this->doExistsByEmail($normalizedEmail);
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Failed to check user existence: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Perform the actual existence check.
     *
     * @param string $normalizedEmail
     * @return bool
     */
    abstract protected function doExistsByEmail(string $normalizedEmail): bool;

    /**
     * {@inheritdoc}
     */
    public function findByEmailDomain(string $domain): array
    {
        if ($domain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        $normalizedDomain = $this->normalizeEmail($domain);

        try {
            return $this->doFindByEmailDomain($normalizedDomain);
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Perform the actual find by domain operation.
     *
     * @param string $normalizedDomain
     * @return array<UserDTO>
     */
    abstract protected function doFindByEmailDomain(string $normalizedDomain): array;
}

/**
 * Factory for creating user repository instances.
 * This allows runtime selection of the ORM implementation.
 */
final class UserRepositoryFactory
{
    /**
     * @param string $type The ORM type ('doctrine', 'eloquent', 'propel', 'cycle', 'yii', 'cake', 'redbean')
     * @param array<string, mixed> $config ORM-specific configuration
     * @return UserRepositoryInterface
     */
    public static function create(string $type, array $config = []): UserRepositoryInterface
    {
        return match ($type) {
            'doctrine' => new \App\Database\Doctrine\DoctrineUserRepository(
                $config['entity_manager'] ?? throw new \RuntimeException('EntityManager required')
            ),
            'eloquent' => new \App\Database\Eloquent\EloquentUserRepository(
                $config['model'] ?? \App\Models\User::class
            ),
            'propel' => new \App\Database\Propel\PropelUserRepository(
                $config['connection'] ?? null
            ),
            'cycle' => new \App\Database\Cycle\CycleUserRepository(
                $config['repository'] ?? throw new \RuntimeException('Repository required')
            ),
            'yii' => new \App\Database\Yii\YiiUserRepository(),
            'cake' => new \App\Database\Cake\CakeUserRepository(
                $config['table'] ?? throw new \RuntimeException('Table required')
            ),
            'redbean' => new \App\Database\RedBean\RedBeanUserRepository(
                $config['database'] ?? null
            ),
            default => throw new \RuntimeException("Unknown ORM type: {$type}"),
        };
    }
}
