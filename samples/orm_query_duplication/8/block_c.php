<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use App\Model\Post;
use App\Model\PostQuery;
use App\Model\Role;
use App\Model\RoleQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User repository using Propel ORM.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return UserPropel|null
     */
    public function getUserWithRelations(int $userId): ?UserPropel
    {
        try {
            $user = UserQuery::create()
                ->joinWithPost()
                ->joinWithUserRole()
                ->usePostQuery()
                    ->orderByCreatedAt('DESC')
                ->endUse()
                ->findPk($userId, $this->connection);

            return $user;
        } catch (PropelException $e) {
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
     * @return array<array{user: UserPropel, postCount: int}>
     */
    public function getUsersWithPostCounts(int $limit = 100): array
    {
        try {
            $users = UserQuery::create()
                ->joinPost()
                ->withColumn('COUNT(Post.Id)', 'PostCount')
                ->groupBy('User.Id')
                ->orderBy('PostCount', 'DESC')
                ->limit($limit)
                ->find($this->connection);

            return $users->map(function ($user) {
                return [
                    'user' => $user,
                    'postCount' => (int) $user->getVirtualColumn('PostCount'),
                ];
            })->toArray();
        } catch (PropelException $e) {
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
     * @return array<array{user: UserPropel, latestPost: Post|null}>
     */
    public function getUsersWithLatestPost(int $limit = 100): array
    {
        try {
            $users = UserQuery::create()
                ->usePostQuery(null, \Propel\Runtime\Util\PropelCriteria::LEFT_JOIN)
                    ->orderByCreatedAt('DESC')
                    ->limit(1)
                ->endUse()
                ->limit($limit)
                ->find($this->connection);

            return $users->map(function ($user) {
                $posts = $user->getPosts();
                $latestPost = !$posts->isEmpty() ? $posts->pop() : null;

                return [
                    'user' => $user,
                    'latestPost' => $latestPost,
                ];
            })->toArray();
        } catch (PropelException $e) {
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
     * @return array<array{role: Role, users: array<UserPropel>}>
     */
    public function getUsersGroupedByRole(): array
    {
        try {
            $roles = RoleQuery::create()
                ->joinWithUserRole()
                ->joinWithUserRoleUser()
                ->find($this->connection);

            return $roles->map(function ($role) {
                return [
                    'role' => $role,
                    'users' => $role->getUsers()->toArray(),
                ];
            })->toArray();
        } catch (PropelException $e) {
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
     * @return array<UserPropel>
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        try {
            $users = UserQuery::create()
                ->usePostQuery()
                    ->filterByCategoryId($categoryIds)
                ->endUse()
                ->distinct()
                ->orderByName('ASC')
                ->find($this->connection);

            return $users->toArray();
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to get users with posts in categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
