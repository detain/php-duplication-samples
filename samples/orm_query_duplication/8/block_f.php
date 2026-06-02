<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return UserEntity|null
     */
    public function getUserWithRelations(int $userId): ?UserEntity
    {
        try {
            return $this->table->get($userId, [
                'contain' => ['Posts', 'Roles'],
            ]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Failed to get user with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users with their post counts.
     *
     * @param int $limit
     * @return array<array{user: UserEntity, postCount: int}>
     */
    public function getUsersWithPostCounts(int $limit = 100): array
    {
        try {
            $users = $this->table->find()
                ->select([
                    'Users.id',
                    'Users.name',
                    'Users.email',
                    'Users.status',
                    'post_count' => $this->table->find()->func()->count('Posts.id'),
                ])
                ->leftJoinWith('Posts')
                ->groupBy(['Users.id'])
                ->orderBy('post_count', 'DESC')
                ->limit($limit)
                ->all()
                ->toArray();

            return array_map(fn($user) => [
                'user' => $user,
                'postCount' => (int) $user->post_count,
            ], $users);
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get users with post counts: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users with their latest post.
     *
     * @param int $limit
     * @return array<array{user: UserEntity, latestPost: PostEntity|null}>
     */
    public function getUsersWithLatestPost(int $limit = 100): array
    {
        try {
            $users = $this->table->find()
                ->contain([
                    'Posts' => function ($query) {
                        return $query->orderBy(['created_at' => 'DESC'])->limit(1);
                    },
                ])
                ->limit($limit)
                ->all()
                ->toArray();

            return array_map(fn($user) => [
                'user' => $user,
                'latestPost' => $user->posts[0] ?? null,
            ], $users);
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get users with latest post: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users grouped by role.
     *
     * @return array<array{role: RoleEntity, users: array<UserEntity>}>
     */
    public function getUsersGroupedByRole(): array
    {
        try {
            $roles = $this->table->Roles->find()
                ->contain('Users')
                ->all()
                ->toArray();

            return array_map(fn($role) => [
                'role' => $role,
                'users' => $role->users ?? [],
            ], $roles);
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get users grouped by role: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users who have posts in specific categories.
     *
     * @param array<int> $categoryIds
     * @return array<UserEntity>
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        try {
            return $this->table->find()
                ->distinct()
                ->contain('Posts')
                ->matching('Posts', function ($query) use ($categoryIds) {
                    return $query->where(['Posts.category_id IN' => $categoryIds]);
                })
                ->orderBy(['name' => 'ASC'])
                ->all()
                ->toArray();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get users with posts in categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
