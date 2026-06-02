<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User repository using Propel ORM.
 * Demonstrates "Pagination" operation.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Get paginated users.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return array{users: array<UserPropel>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $query = UserQuery::create();

        if (!empty($filters['status'])) {
            $query->filterByStatus($filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('Name LIKE ? OR Email LIKE ?', '%' . $filters['search'] . '%', '%' . $filters['search'] . '%');
        }

        $total = $query->count($this->connection);

        $users = $query->orderByCreatedAt('desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->find($this->connection);

        $totalPages = (int) ceil($total / $perPage);

        return [
            'users' => $users->toArray(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Get paginated users with cursor-based pagination.
     *
     * @param int $cursor
     * @param int $limit
     * @return array{users: array<UserPropel>, nextCursor: int|null, hasMore: bool}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        $query = UserQuery::create()
            ->filterById($cursor, \Propel\Runtime\Util\PropelCriterion::GREATER_THAN)
            ->orderById('asc')
            ->limit($limit + 1);

        try {
            $users = $query->find($this->connection);
            $hasMore = $users->count() > $limit;

            if ($hasMore) {
                $users = $users->slice(0, $limit);
            }

            $nextCursor = !$users->isEmpty() ? $users->last()->getId() : null;

            return [
                'users' => $users->toArray(),
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
            ];
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get paginated users sorted by activity.
     *
     * @param int $page
     * @param int $perPage
     * @return array{users: array<UserPropel>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $query = UserQuery::create()
            ->joinWithPost()
            ->withColumn('COUNT(Post.Id)', 'PostCount')
            ->groupBy('User.Id');

        $total = $query->count($this->connection);

        $users = $query->orderBy('PostCount', 'desc')
            ->orderByCreatedAt('desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->find($this->connection);

        $totalPages = (int) ceil($total / $perPage);

        return [
            'users' => $users->toArray(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }
}
