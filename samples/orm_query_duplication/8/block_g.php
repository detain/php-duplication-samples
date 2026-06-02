<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\Role;
use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * User repository using RedBeanPHP ORM.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return User|null
     */
    public function getUserWithRelations(int $userId): ?User
    {
        try {
            $bean = R::load('user', $userId);

            if ($bean->id === 0) {
                return null;
            }

            R::loadFor('user', $bean->id, ['ownPostList', 'sharedRoleList']);

            return $this->mapToEntity($bean);
        } catch (RedException $e) {
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
            $sql = 'SELECT user.*,
                    (SELECT COUNT(*) FROM post WHERE post.author_id = user.id) as postCount
                    FROM user
                    ORDER BY postCount DESC
                    LIMIT ?';

            $beans = R::convertToBeans('user', R::getAll($sql, [$limit]));

            return array_map(function ($bean) {
                $postCount = R::count('post', 'author_id = ?', [$bean->id]);
                return [
                    'user' => $this->mapToEntity($bean),
                    'postCount' => (int) $postCount,
                ];
            }, $beans);
        } catch (RedException $e) {
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
            $beans = R::find('user', '1=1 LIMIT ?', [$limit]);

            return array_map(function ($bean) {
                $latestPostBean = R::findOne('post', 'author_id = ? ORDER BY created_at DESC LIMIT 1', [$bean->id]);

                return [
                    'user' => $this->mapToEntity($bean),
                    'latestPost' => $latestPostBean ? $this->mapPostToEntity($latestPostBean) : null,
                ];
            }, $beans);
        } catch (RedException $e) {
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
            $roleBeans = R::findAll('role', 'ORDER BY name ASC');

            return array_map(function ($roleBean) {
                $userBeans = R::getAll(
                    'SELECT user.* FROM user
                     JOIN user_role ON user_role.user_id = user.id
                     WHERE user_role.role_id = ?',
                    [$roleBean->id]
                );

                return [
                    'role' => $this->mapRoleToEntity($roleBean),
                    'users' => array_map([$this, 'mapToEntity'], R::convertToBeans('user', $userBeans)),
                ];
            }, $roleBeans);
        } catch (RedException $e) {
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
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $sql = "SELECT DISTINCT user.* FROM user
                    JOIN post ON post.author_id = user.id
                    WHERE post.category_id IN ({$placeholders})
                    ORDER BY user.name ASC";

            $beans = R::convertToBeans('user', R::getAll($sql, $categoryIds));

            return array_map([$this, 'mapToEntity'], $beans);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to get users with posts in categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function mapToEntity(\RedBeanPHP\OODBBean $bean): User
    {
        $user = new User();
        $user->id = (int) $bean->id;
        $user->email = $bean->email;
        $user->name = $bean->name ?? '';
        $user->createdAt = isset($bean->createdAt)
            ? new \DateTime($bean->createdAt)
            : new \DateTime();

        return $user;
    }

    private function mapPostToEntity(\RedBeanPHP\OODBBean $bean): Post
    {
        $post = new Post();
        $post->id = (int) $bean->id;
        $post->title = $bean->title ?? '';
        $post->content = $bean->content ?? '';

        return $post;
    }

    private function mapRoleToEntity(\RedBeanPHP\OODBBean $bean): Role
    {
        $role = new Role();
        $role->id = (int) $bean->id;
        $role->name = $bean->name ?? '';

        return $role;
    }
}
