<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use App\Entity\User;
use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * User repository using RedBeanPHP ORM.
 * Demonstrates "Pagination" operation.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

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

        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE ? OR email LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

        try {
            $total = R::count('user', $whereClause, $params);

            $offset = ($page - 1) * $perPage;
            $beans = R::find('user', "{$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$perPage, $offset]));

            $users = array_map([$this, 'mapToEntity'], $beans);
            $totalPages = (int) ceil($total / $perPage);

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ];
        } catch (RedException $e) {
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
     * @return array{users: array<User>, nextCursor: int|null, hasMore: bool}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        try {
            $beans = R::find('user', 'id > ? ORDER BY id ASC LIMIT ?', [$cursor, $limit + 1]);

            $usersArray = array_values($beans);
            $hasMore = count($usersArray) > $limit;

            if ($hasMore) {
                array_pop($usersArray);
            }

            $users = array_map([$this, 'mapToEntity'], $usersArray);
            $nextCursor = !empty($users) ? end($users)->id : null;

            return [
                'users' => $users,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
            ];
        } catch (RedException $e) {
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

        try {
            $total = R::getRow('SELECT COUNT(DISTINCT author_id) as cnt FROM post');
            $total = (int) ($total['cnt'] ?? 0);

            $offset = ($page - 1) * $perPage;
            $beans = R::find('user',
                'id IN (SELECT author_id FROM post) ORDER BY (SELECT COUNT(*) FROM post WHERE post.author_id = user.id) DESC LIMIT ? OFFSET ?',
                [$perPage, $offset]
            );

            $users = array_map([$this, 'mapToEntity'], $beans);
            $totalPages = (int) ceil($total / $perPage);

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ];
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
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
}
