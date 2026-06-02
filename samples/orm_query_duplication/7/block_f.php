<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Pagination" operation.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Get paginated users.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return array{users: array<UserEntity>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $query = $this->table->find();

        if (!empty($filters['status'])) {
            $query->where(['status' => $filters['status']]);
        }

        if (!empty($filters['search'])) {
            $query->where([
                'OR' => [
                    ['name LIKE' => '%' . $filters['search'] . '%'],
                    ['email LIKE' => '%' . $filters['search'] . '%'],
                ],
            ]);
        }

        try {
            $total = $query->count();

            $users = $query->orderBy(['created_at' => 'DESC'])
                ->limit($perPage)
                ->page($page)
                ->all()
                ->toArray();

            $totalPages = (int) ceil($total / $perPage);

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ];
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get paginated users with cursor-based pagination.
     *
     * @param int $cursor
     * @param int $limit
     * @return array{users: array<UserEntity>, nextCursor: int|null, hasMore: bool}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        try {
            $query = $this->table->find()
                ->where(['id >' => $cursor])
                ->orderBy(['id' => 'ASC'])
                ->limit($limit + 1);

            $users = $query->all()->toArray();
            $hasMore = count($users) > $limit;

            if ($hasMore) {
                $users = array_slice($users, 0, $limit);
            }

            $nextCursor = !empty($users) ? end($users)->id : null;

            return [
                'users' => $users,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
            ];
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
     * @return array{users: array<UserEntity>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        try {
            $query = $this->table->find()
                ->select([
                    'Users.id',
                    'Users.name',
                    'Users.email',
                    'Users.status',
                    'Users.created_at',
                    'post_count' => $query->func()->count('Posts.id'),
                ])
                ->leftJoinWith('Posts')
                ->groupBy(['Users.id'])
                ->orderBy(['post_count' => 'DESC', 'created_at' => 'DESC']);

            $total = $query->count();

            $users = $query->limit($perPage)->page($page)->all()->toArray();

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($total / $perPage),
            ];
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
