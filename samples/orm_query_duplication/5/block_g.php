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
 * Demonstrates "Search users with WHERE clause" operation.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

    /**
     * Search users with multiple WHERE conditions.
     *
     * @param array{name?: string, email?: string, status?: string} $criteria
     * @param int $limit
     * @param int $offset
     * @return array<User>
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if (!empty($criteria['name'])) {
            $conditions[] = 'name LIKE ?';
            $params[] = '%' . $criteria['name'] . '%';
        }

        if (!empty($criteria['email'])) {
            $conditions[] = 'email LIKE ?';
            $params[] = '%' . $criteria['email'] . '%';
        }

        if (!empty($criteria['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $criteria['status'];
        }

        if (!empty($criteria['createdAfter'])) {
            $conditions[] = 'created_at >= ?';
            $params[] = $criteria['createdAfter'] instanceof \DateTimeInterface
                ? $criteria['createdAfter']->format('Y-m-d H:i:s')
                : $criteria['createdAfter'];
        }

        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';
        $limitClause = "LIMIT {$limit} OFFSET {$offset}";

        try {
            $beans = R::find('user', "{$whereClause} ORDER BY created_at DESC {$limitClause}", $params);

            return array_map([$this, 'mapToEntity'], $beans);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Search users with OR conditions.
     *
     * @param array<string, string> $criteria
     * @return array<User>
     */
    public function searchUsersOr(array $criteria): array
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            if ($key === 'name' || $key === 'email') {
                $conditions[] = "{$key} LIKE ?";
                $params[] = '%' . $value . '%';
            }
        }

        if (empty($conditions)) {
            return [];
        }

        $whereClause = '(' . implode(' OR ', $conditions) . ')';

        try {
            $beans = R::find('user', "{$whereClause} ORDER BY created_at DESC", $params);

            return array_map([$this, 'mapToEntity'], $beans);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users by status with count.
     *
     * @param string $status
     * @return array{users: array<User>, count: int}
     */
    public function findByStatus(string $status): array
    {
        try {
            $count = R::count('user', 'status = ?', [$status]);
            $beans = R::find('user', 'status = ? ORDER BY created_at DESC', [$status]);

            return [
                'users' => array_map([$this, 'mapToEntity'], $beans),
                'count' => $count,
            ];
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to find users by status: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Advanced search with complex criteria.
     *
     * @param array{
     *     name?: string,
     *     email?: string,
     *     status?: string[],
     *     createdBetween?: array{start: \DateTimeInterface, end: \DateTimeInterface}
     * } $criteria
     * @return array<User>
     */
    public function advancedSearch(array $criteria): array
    {
        $conditions = [];
        $params = [];

        if (!empty($criteria['name'])) {
            $conditions[] = 'name LIKE ?';
            $params[] = '%' . $criteria['name'] . '%';
        }

        if (!empty($criteria['email'])) {
            $conditions[] = 'email LIKE ?';
            $params[] = '%' . $criteria['email'] . '%';
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $placeholders = implode(',', array_fill(0, count($criteria['status']), '?'));
            $conditions[] = "status IN ({$placeholders})";
            $params = array_merge($params, $criteria['status']);
        }

        if (!empty($criteria['createdBetween'])) {
            $conditions[] = 'created_at BETWEEN ? AND ?';
            $params[] = $criteria['createdBetween']['start'] instanceof \DateTimeInterface
                ? $criteria['createdBetween']['start']->format('Y-m-d H:i:s')
                : $criteria['createdBetween']['start'];
            $params[] = $criteria['createdBetween']['end'] instanceof \DateTimeInterface
                ? $criteria['createdBetween']['end']->format('Y-m-d H:i:s')
                : $criteria['createdBetween']['end'];
        }

        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

        try {
            $beans = R::find('user', "{$whereClause} ORDER BY created_at DESC", $params);

            return array_map([$this, 'mapToEntity'], $beans);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
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
