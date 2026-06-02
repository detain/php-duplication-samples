<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Search users with WHERE clause" operation.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Search users with multiple WHERE conditions.
     *
     * @param array{name?: string, email?: string, status?: string} $criteria
     * @param int $limit
     * @param int $offset
     * @return array<UserEntity>
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        $query = $this->table->find();

        if (!empty($criteria['name'])) {
            $query->where(['name LIKE' => '%' . $criteria['name'] . '%']);
        }

        if (!empty($criteria['email'])) {
            $query->where(['email LIKE' => '%' . $criteria['email'] . '%']);
        }

        if (!empty($criteria['status'])) {
            $query->where(['status' => $criteria['status']]);
        }

        if (!empty($criteria['createdAfter'])) {
            $query->where(['created_at >=' => $criteria['createdAfter']]);
        }

        try {
            return $query->orderBy(['created_at' => 'DESC'])
                ->limit($limit)
                ->offset($offset)
                ->all()
                ->toArray();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
     * @return array<UserEntity>
     */
    public function searchUsersOr(array $criteria): array
    {
        $query = $this->table->find();

        $conditions = [];

        foreach ($criteria as $key => $value) {
            if ($key === 'name' || $key === 'email') {
                $conditions[] = [$key . ' LIKE' => '%' . $value . '%'];
            }
        }

        if (!empty($conditions)) {
            $query->where(['OR' => $conditions]);
        }

        try {
            return $query->orderBy(['created_at' => 'DESC'])
                ->all()
                ->toArray();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
     * @return array{users: array<UserEntity>, count: int}
     */
    public function findByStatus(string $status): array
    {
        $query = $this->table->find()->where(['status' => $status]);

        try {
            return [
                'users' => $query->orderBy(['created_at' => 'DESC'])->all()->toArray(),
                'count' => $query->count(),
            ];
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
     * @return array<UserEntity>
     */
    public function advancedSearch(array $criteria): array
    {
        $query = $this->table->find();

        if (!empty($criteria['name'])) {
            $query->where(['name LIKE' => '%' . $criteria['name'] . '%']);
        }

        if (!empty($criteria['email'])) {
            $query->where(['email LIKE' => '%' . $criteria['email'] . '%']);
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $query->where(['status IN' => $criteria['status']]);
        }

        if (!empty($criteria['createdBetween'])) {
            $query->where([
                'created_at BETWEEN ? AND ?' => [
                    $criteria['createdBetween']['start'],
                    $criteria['createdBetween']['end'],
                ],
            ]);
        }

        try {
            return $query->orderBy(['created_at' => 'DESC'])->all()->toArray();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
