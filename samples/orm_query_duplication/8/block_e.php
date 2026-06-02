<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use App\Models\Post as PostYii;
use App\Models\Role as RoleYii;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
 */
final class YiiUserRepository
{
    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return UserYii|null
     */
    public function getUserWithRelations(int $userId): ?UserYii
    {
        try {
            return UserYii::find()
                ->with(['posts', 'roles'])
                ->where(['id' => $userId])
                ->one();
        } catch (Exception $e) {
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
     * @return array<array{user: UserYii, postCount: int}>
     */
    public function getUsersWithPostCounts(int $limit = 100): array
    {
        try {
            $users = UserYii::find()
                ->select([
                    'user.*',
                    'COUNT(post.id) as postCount',
                ])
                ->leftJoin('post', 'post.author_id = user.id')
                ->groupBy('user.id')
                ->orderBy('postCount', SORT_DESC)
                ->limit($limit)
                ->all();

            return array_map(fn($user) => [
                'user' => $user,
                'postCount' => (int) $user->postCount,
            ], $users);
        } catch (Exception $e) {
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
     * @return array<array{user: UserYii, latestPost: PostYii|null}>
     */
    public function getUsersWithLatestPost(int $limit = 100): array
    {
        try {
            $users = UserYii::find()
                ->with([
                    'posts' => function ($query) {
                        $query->orderBy(['created_at' => SORT_DESC])->limit(1);
                    },
                ])
                ->limit($limit)
                ->all();

            return array_map(fn($user) => [
                'user' => $user,
                'latestPost' => $user->posts[0] ?? null,
            ], $users);
        } catch (Exception $e) {
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
     * @return array<array{role: RoleYii, users: array<UserYii>}>
     */
    public function getUsersGroupedByRole(): array
    {
        try {
            $roles = RoleYii::find()->all();
            $grouped = [];

            foreach ($roles as $role) {
                $grouped[] = [
                    'role' => $role,
                    'users' => $role->getUsers()->all(),
                ];
            }

            return $grouped;
        } catch (Exception $e) {
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
     * @return array<UserYii>
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        try {
            return UserYii::find()
                ->distinct()
                ->joinWith('posts')
                ->where(['in', 'post.category_id', $categoryIds])
                ->orderBy('name', 'ASC')
                ->all();
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to get users with posts in categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
