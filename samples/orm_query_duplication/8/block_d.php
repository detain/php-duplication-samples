<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\Select\Repository;
use App\Entity\User;
use App\Entity\Post;
use App\Entity\Role;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
 */
final class CycleUserRepository extends Repository
{
    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return User|null
     */
    public function getUserWithRelations(int $userId): ?User
    {
        try {
            return $this->select()
                ->with(['posts', 'roles'])
                ->where('id', $userId)
                ->fetchOne();
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
     * @return array<array{user: User, postCount: int}>
     */
    public function getUsersWithPostCounts(int $limit = 100): array
    {
        try {
            $results = $this->select()
                ->with('posts')
                ->orderBy('posts', 'DESC')
                ->limit($limit)
                ->fetchAll();

            return array_map(function ($user) {
                return [
                    'user' => $user,
                    'postCount' => count($user->posts ?? []),
                ];
            }, $results);
        } catch (\Exception $e) {
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
     * @return array<array{user: User, latestPost: Post|null}>
     */
    public function getUsersWithLatestPost(int $limit = 100): array
    {
        try {
            $results = $this->select()
                ->with(['posts' => function ($select) {
                    $select->orderBy('createdAt', 'DESC')->limit(1);
                }])
                ->limit($limit)
                ->fetchAll();

            return array_map(function ($user) {
                $posts = $user->posts ?? [];
                $latestPost = !empty($posts) ? $posts[0] : null;

                return [
                    'user' => $user,
                    'latestPost' => $latestPost,
                ];
            }, $results);
        } catch (\Exception $e) {
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
     * @return array<array{role: Role, users: array<User>}>
     */
    public function getUsersGroupedByRole(): array
    {
        try {
            $roleRepository = $this->em->getRepository(Role::class);
            $roles = $roleRepository->select()->fetchAll();

            return array_map(function ($role) {
                return [
                    'role' => $role,
                    'users' => $role->users ?? [],
                ];
            }, $roles);
        } catch (\Exception $e) {
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
     * @return array<User>
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        try {
            return $this->select()
                ->distinct()
                ->with('posts')
                ->where('posts.category_id', 'IN', $categoryIds)
                ->orderBy('name', 'ASC')
                ->fetchAll();
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Failed to get users with posts in categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
