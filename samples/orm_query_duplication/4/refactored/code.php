<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for user deletion operations.
 */
interface DeletableUserRepositoryInterface
{
    /**
     * Delete user with optional cascade.
     *
     * @param int $userId
     * @param bool $cascade
     * @return bool
     */
    public function deleteUser(int $userId, bool $cascade = true): bool;

    /**
     * Soft delete user.
     *
     * @param int $userId
     * @return UserDTO
     */
    public function softDeleteUser(int $userId): UserDTO;

    /**
     * Batch delete users.
     *
     * @param array<int> $userIds
     * @param bool $cascade
     * @return int Number of deleted users
     */
    public function deleteUsers(array $userIds, bool $cascade = true): int;
}

/**
 * Data transfer object for delete operation results.
 */
final readonly class DeleteResult
{
    public function __construct(
        public bool $success,
        public int $deletedCount,
        public array $failedIds = [],
        public ?string $error = null,
    ) {}

    public static function success(int $count): self
    {
        return new self(true, $count);
    }

    public static function failure(string $error, array $failedIds = []): self
    {
        return new self(false, 0, $failedIds, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}

/**
 * Delete strategy enum.
 */
enum DeleteStrategy
{
    case HARD_DELETE;
    case SOFT_DELETE;
    case CASCADE;
}

/**
 * Base delete service with common deletion logic.
 */
abstract class AbstractDeletableUserRepository implements DeletableUserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function deleteUser(int $userId, bool $cascade = true): bool
    {
        $user = $this->findUser($userId);

        if ($user === null) {
            throw new \RuntimeException("User with ID {$userId} not found", 404);
        }

        return $this->performDelete($user, $cascade);
    }

    /**
     * {@inheritdoc}
     */
    public function softDeleteUser(int $userId): UserDTO
    {
        $user = $this->findUser($userId);

        if ($user === null) {
            throw new \RuntimeException("User with ID {$userId} not found", 404);
        }

        return $this->performSoftDelete($user);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUsers(array $userIds, bool $cascade = true): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;
        $failedIds = [];

        $this->beginTransaction();

        try {
            foreach ($userIds as $userId) {
                try {
                    $user = $this->findUser($userId);

                    if ($user !== null) {
                        $this->performDelete($user, $cascade);
                        $count++;
                    } else {
                        $failedIds[] = $userId;
                    }
                } catch (\Exception $e) {
                    $failedIds[] = $userId;
                }
            }

            if (!empty($failedIds) && $count === 0) {
                throw new \RuntimeException('All deletions failed');
            }

            $this->commitTransaction();

            return $count;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Find user by ID.
     *
     * @param int $id
     * @return mixed
     */
    abstract protected function findUser(int $id): mixed;

    /**
     * Perform actual deletion.
     *
     * @param mixed $user
     * @param bool $cascade
     * @return bool
     */
    abstract protected function performDelete(mixed $user, bool $cascade): bool;

    /**
     * Perform soft deletion.
     *
     * @param mixed $user
     * @return UserDTO
     */
    abstract protected function performSoftDelete(mixed $user): UserDTO;

    /**
     * Begin transaction.
     */
    protected function beginTransaction(): void {}

    /**
     * Commit transaction.
     */
    protected function commitTransaction(): void {}

    /**
     * Rollback transaction.
     */
    protected function rollbackTransaction(): void {}

    /**
     * Delete related posts.
     *
     * @param int $userId
     */
    protected function deleteUserPosts(int $userId): void {}

    /**
     * Delete user roles.
     *
     * @param int $userId
     */
    protected function deleteUserRoles(int $userId): void {}
}

/**
 * Factory for creating deletable repositories.
 */
final class DeletableUserRepositoryFactory
{
    public static function create(string $type, array $config = []): DeletableUserRepositoryInterface
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
