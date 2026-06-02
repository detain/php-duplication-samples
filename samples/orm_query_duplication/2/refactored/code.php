<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for user services across different ORM implementations.
 * This abstraction enables consistent user creation patterns regardless
 * of the underlying ORM choice.
 */
interface UserServiceInterface
{
    /**
     * Create a new user and return the model with generated ID.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserDTO
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): UserDTO;

    /**
     * Create multiple users in a batch.
     *
     * @param array<array{name: string, email: string, password: string}> $users
     * @return array<UserDTO>
     */
    public function createUsersBatch(array $users): array;

    /**
     * Create user with role assignments.
     *
     * @param array{name: string, email: string, password: string} $data
     * @param array<int> $roleIds
     * @return UserDTO
     */
    public function createUserWithRoles(array $data, array $roleIds = []): UserDTO;

    /**
     * Find or create user by email.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserDTO
     */
    public function findOrCreateUser(array $data): UserDTO;
}

/**
 * Data Transfer Object representing a User for creation/update operations.
 */
final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public array $roleIds = [],
    ) {}

    /**
     * Create from raw array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            roleIds: array_filter((array) ($data['role_ids'] ?? $data['roleIds'] ?? [])),
        );
    }
}

/**
 * Validation result object.
 */
final class ValidationResult
{
    private function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): string
    {
        return $this->errors[0] ?? 'Validation failed';
    }
}

/**
 * Abstract base service providing common validation and DTO conversion.
 */
abstract class AbstractUserService implements UserServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUser(array $data): UserDTO
    {
        $dto = CreateUserDTO::fromArray($data);
        $this->validateUserData($dto);

        return $this->doCreateUser($dto);
    }

    /**
     * Perform the actual user creation.
     *
     * @param CreateUserDTO $dto
     * @return UserDTO
     */
    abstract protected function doCreateUser(CreateUserDTO $dto): UserDTO;

    /**
     * {@inheritdoc}
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $dtos = [];
        foreach ($users as $data) {
            $dto = CreateUserDTO::fromArray($data);
            $this->validateUserData($dto);
            $dtos[] = $dto;
        }

        return $this->doCreateUsersBatch($dtos);
    }

    /**
     * Perform batch user creation.
     *
     * @param array<CreateUserDTO> $dtos
     * @return array<UserDTO>
     */
    abstract protected function doCreateUsersBatch(array $dtos): array;

    /**
     * {@inheritdoc}
     */
    public function createUserWithRoles(array $data, array $roleIds = []): UserDTO
    {
        $dto = CreateUserDTO::fromArray($data);
        $dto = new CreateUserDTO(
            name: $dto->name,
            email: $dto->email,
            password: $dto->password,
            roleIds: $roleIds,
        );

        $this->validateUserData($dto);

        return $this->doCreateUserWithRoles($dto);
    }

    /**
     * Perform user creation with roles.
     *
     * @param CreateUserDTO $dto
     * @return UserDTO
     */
    abstract protected function doCreateUserWithRoles(CreateUserDTO $dto): UserDTO;

    /**
     * {@inheritdoc}
     */
    public function findOrCreateUser(array $data): UserDTO
    {
        $dto = CreateUserDTO::fromArray($data);
        $this->validateUserData($dto);

        return $this->doFindOrCreateUser($dto);
    }

    /**
     * Perform find or create operation.
     *
     * @param CreateUserDTO $dto
     * @return UserDTO
     */
    abstract protected function doFindOrCreateUser(CreateUserDTO $dto): UserDTO;

    /**
     * Validate user data.
     *
     * @param CreateUserDTO $dto
     * @throws RuntimeException
     */
    protected function validateUserData(CreateUserDTO $dto): void
    {
        $errors = [];

        if ($dto->name === '') {
            $errors[] = 'User name is required';
        }

        if ($dto->email === '') {
            $errors[] = 'User email is required';
        } elseif (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if ($dto->password === '') {
            $errors[] = 'User password is required';
        } elseif (strlen($dto->password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (!empty($errors)) {
            throw new RuntimeException(implode('; ', $errors));
        }
    }

    /**
     * Normalize email for case-insensitive storage.
     *
     * @param string $email
     * @return string
     */
    protected function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Hash password for storage.
     *
     * @param string $password
     * @return string
     */
    protected function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Convert ORM model to DTO.
     *
     * @param mixed $model
     * @param array $relations
     * @return UserDTO
     */
    abstract protected function toDTO(mixed $model, array $relations = []): UserDTO;
}

/**
 * Factory for creating user service instances.
 */
final class UserServiceFactory
{
    /**
     * @param string $type The ORM type
     * @param array<string, mixed> $config
     * @return UserServiceInterface
     */
    public static function create(string $type, array $config = []): UserServiceInterface
    {
        return match ($type) {
            'doctrine' => new \App\Database\Doctrine\DoctrineUserService(
                $config['entity_manager'] ?? throw new \RuntimeException('EntityManager required')
            ),
            'eloquent' => new \App\Database\Eloquent\EloquentUserService(),
            'propel' => new \App\Database\Propel\PropelUserService(
                $config['connection'] ?? null
            ),
            'cycle' => new \App\Database\Cycle\CycleUserService(
                $config['entity_manager'] ?? throw new \RuntimeException('EntityManager required')
            ),
            'yii' => new \App\Database\Yii\YiiUserService(),
            'cake' => new \App\Database\Cake\CakeUserService(
                $config['table'] ?? throw new \RuntimeException('Table required')
            ),
            'redbean' => new \App\Database\RedBean\RedBeanUserService(
                $config['database'] ?? null
            ),
            default => throw new \RuntimeException("Unknown ORM type: {$type}"),
        };
    }
}
