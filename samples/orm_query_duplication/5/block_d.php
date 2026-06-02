<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\Select\Repository;
use App\Entity\User;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Search users with WHERE clause" operation.
 */
final class CycleUserRepository extends Repository
{
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
        $select = $this->select();

        if (!empty($criteria['name'])) {
            $select->where('name', 'LIKE', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['email'])) {
            $select->where('email', 'LIKE', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['status'])) {
            $select->where('status', $criteria['status']);
        }

        if (!empty($criteria['createdAfter'])) {
            $select->where('createdAt', '>=', $criteria['createdAfter']);
        }

        return $select
            ->orderBy('createdAt', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->fetchAll();
    }

    /**
     * Search users with OR conditions.
     *
     * @param array<string, string> $criteria
     * @return array<User>
     */
    public function searchUsersOr(array $criteria): array
    {
        $select = $this->select();

        $conditions = [];

        if (!empty($criteria['name'])) {
            $conditions[] = ['name', 'LIKE', '%' . $criteria['name'] . '%'];
        }

        if (!empty($criteria['email'])) {
            $conditions[] = ['email', 'LIKE', '%' . $criteria['email'] . '%'];
        }

        if (!empty($conditions)) {
            $select->where([
                ['OR' => $conditions],
            ]);
        }

        return $select->orderBy('createdAt', 'DESC')->fetchAll();
    }

    /**
     * Find users by status with count.
     *
     * @param string $status
     * @return array{users: array<User>, count: int}
     */
    public function findByStatus(string $status): array
    {
        $select = $this->select()->where('status', $status);

        $count = $select->count();
        $users = $this->select()
            ->where('status', $status)
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();

        return ['users' => $users, 'count' => $count];
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
        $select = $this->select();

        if (!empty($criteria['name'])) {
            $select->where('name', 'LIKE', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['email'])) {
            $select->where('email', 'LIKE', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $select->where('status', 'IN', $criteria['status']);
        }

        if (!empty($criteria['createdBetween'])) {
            $select->where('createdAt', 'BETWEEN', [
                $criteria['createdBetween']['start'],
                $criteria['createdBetween']['end'],
            ]);
        }

        return $select->orderBy('createdAt', 'DESC')->fetchAll();
    }
}
