<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\Select\Repository;
use App\Entity\User;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Pagination" operation.
 */
final class CycleUserRepository extends Repository
{
    /**
     * Get paginated users.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return array{users: array<User>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $select = $this->select();

        if (!empty($filters['status'])) {
            $select->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $select->where('name', 'LIKE', '%' . $filters['search'] . '%');
            $select->orWhere('email', 'LIKE', '%' . $filters['search'] . '%');
        }

        $total = $select->count();

        $users = $select->orderBy('createdAt', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->fetchAll();

        $totalPages = (int) ceil($total / $perPage);

        return [
            'users' => $users,
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
     * @return array{users: array<User>, nextCursor: int|null, hasMore: bool}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        $select = $this->select()
            ->where('id', '>', $cursor)
            ->orderBy('id', 'ASC')
            ->limit($limit + 1);

        try {
            $users = $select->fetchAll();
            $hasMore = count($users) > $limit;

            if ($hasMore) {
                array_pop($users);
            }

            $nextCursor = !empty($users) ? end($users)->id : null;

            return [
                'users' => $users,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
            ];
        } catch (\Exception $e) {
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
     * @return array{users: array<User>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $select = $this->select()->with('posts');
        $total = $select->count();

        $users = $select->orderBy('posts', 'DESC')
            ->orderBy('createdAt', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->fetchAll();

        $totalPages = (int) ceil($total / $perPage);

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ];
    }
}
