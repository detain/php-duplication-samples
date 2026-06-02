<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for user relations operations.
 */
interface UserRelationsRepositoryInterface
{
    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return UserWithRelationsDTO
     */
    public function getUserWithRelations(int $userId): ?UserWithRelationsDTO;

    /**
     * Get users with their post counts.
     *
     * @param int $limit
     * @return array<UserWithPostCountDTO>
     */
    public function getUsersWithPostCounts(int $limit = 100): array;

    /**
     * Get users with their latest post.
     *
     * @param int $limit
     * @return array<UserWithLatestPostDTO>
     */
    public function getUsersWithLatestPost(int $limit = 100): array;

    /**
     * Get users grouped by role.
     *
     * @return array<RoleWithUsersDTO>
     */
    public function getUsersGroupedByRole(): array;

    /**
     * Get users who have posts in specific categories.
     *
     * @param array<int> $categoryIds
     * @return array<UserDTO>
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array;
}

/**
 * User with relations data transfer object.
 */
final readonly class UserWithRelationsDTO
{
    public function __construct(
        public UserDTO $user,
        public array $posts,
        public array $roles,
    ) {}
}

/**
 * User with post count data transfer object.
 */
final readonly class UserWithPostCountDTO
{
    public function __construct(
        public UserDTO $user,
        public int $postCount,
    ) {}
}

/**
 * User with latest post data transfer object.
 */
final readonly class UserWithLatestPostDTO
{
    public function __construct(
        public UserDTO $user,
        public ?PostDTO $latestPost,
    ) {}
}

/**
 * Role with users data transfer object.
 */
final readonly class RoleWithUsersDTO
{
    public function __construct(
        public RoleDTO $role,
        public array $users,
    ) {}
}

/**
 * Abstract base relations repository.
 */
abstract class AbstractUserRelationsRepository implements UserRelationsRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserWithRelations(int $userId): ?UserWithRelationsDTO
    {
        $user = $this->findUserWithRelations($userId);

        if ($user === null) {
            return null;
        }

        return $this->mapUserWithRelations($user);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersWithPostCounts(int $limit = 100): array
    {
        $users = $this->findUsersWithPostCounts($limit);

        return $this->mapUsersWithPostCounts($users);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersWithLatestPost(int $limit = 100): array
    {
        $users = $this->findUsersWithLatestPost($limit);

        return $this->mapUsersWithLatestPost($users);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersGroupedByRole(): array
    {
        $roles = $this->findUsersGroupedByRole();

        return $this->mapRolesWithUsers($roles);
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $users = $this->findUsersWithPostsInCategories($categoryIds);

        return $this->mapUserDTOs($users);
    }

    /**
     * Find user with relations.
     *
     * @param int $userId
     * @return mixed
     */
    abstract protected function findUserWithRelations(int $userId): mixed;

    /**
     * Find users with post counts.
     *
     * @param int $limit
     * @return array
     */
    abstract protected function findUsersWithPostCounts(int $limit): array;

    /**
     * Find users with latest post.
     *
     * @param int $limit
     * @return array
     */
    abstract protected function findUsersWithLatestPost(int $limit): array;

    /**
     * Find users grouped by role.
     *
     * @return array
     */
    abstract protected function findUsersGroupedByRole(): array;

    /**
     * Find users with posts in categories.
     *
     * @param array<int> $categoryIds
     * @return array
     */
    abstract protected function findUsersWithPostsInCategories(array $categoryIds): array;

    /**
     * Map user entity to DTO.
     *
     * @param mixed $user
     * @return UserDTO
     */
    abstract protected function mapUserDTO(mixed $user): UserDTO;

    /**
     * Map user with relations result.
     *
     * @param mixed $user
     * @return UserWithRelationsDTO
     */
    abstract protected function mapUserWithRelations(mixed $user): UserWithRelationsDTO;

    /**
     * Map users with post counts result.
     *
     * @param array $users
     * @return array<UserWithPostCountDTO>
     */
    abstract protected function mapUsersWithPostCounts(array $users): array;

    /**
     * Map users with latest post result.
     *
     * @param array $users
     * @return array<UserWithLatestPostDTO>
     */
    abstract protected function mapUsersWithLatestPost(array $users): array;

    /**
     * Map roles with users result.
     *
     * @param array $roles
     * @return array<RoleWithUsersDTO>
     */
    abstract protected function mapRolesWithUsers(array $roles): array;

    /**
     * Map multiple users to DTOs.
     *
     * @param array $users
     * @return array<UserDTO>
     */
    abstract protected function mapUserDTOs(array $users): array;
}

/**
 * Factory for creating user relations repositories.
 */
final class UserRelationsRepositoryFactory
{
    public static function create(string $type, array $config = []): UserRelationsRepositoryInterface
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
