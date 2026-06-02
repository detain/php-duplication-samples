<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Search users with WHERE clause" operation.
 */
final class YiiUserRepository
{
    /**
     * Search users with multiple WHERE conditions.
     *
     * @param array{name?: string, email?: string, status?: string} $criteria
     * @param int $limit
     * @param int $offset
     * @return array<UserYii>
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        $query = UserYii::find();

        if (!empty($criteria['name'])) {
            $query->andWhere(['LIKE', 'name', $criteria['name']]);
        }

        if (!empty($criteria['email'])) {
            $query->andWhere(['LIKE', 'email', $criteria['email']]);
        }

        if (!empty($criteria['status'])) {
            $query->andWhere(['status' => $criteria['status']]);
        }

        if (!empty($criteria['createdAfter'])) {
            $query->andWhere(['>=', 'created_at', $criteria['createdAfter']]);
        }

        try {
            return $query->orderBy(['created_at' => SORT_DESC])
                ->offset($offset)
                ->limit($limit)
                ->all();
        } catch (Exception $e) {
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
     * @return array<UserYii>
     */
    public function searchUsersOr(array $criteria): array
    {
        $condition = ['or'];

        foreach ($criteria as $key => $value) {
            if ($key === 'name' || $key === 'email') {
                $condition[] = ['LIKE', $key, $value];
            }
        }

        try {
            return UserYii::find()
                ->where($condition)
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
        } catch (Exception $e) {
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
     * @return array{users: array<UserYii>, count: int}
     */
    public function findByStatus(string $status): array
    {
        $query = UserYii::find()->where(['status' => $status]);

        try {
            return [
                'users' => $query->orderBy(['created_at' => SORT_DESC])->all(),
                'count' => $query->count(),
            ];
        } catch (Exception $e) {
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
     *     roleIds?: int[],
     *     createdBetween?: array{start: \DateTimeInterface, end: \DateTimeInterface}
     * } $criteria
     * @return array<UserYii>
     */
    public function advancedSearch(array $criteria): array
    {
        $query = UserYii::find();

        if (!empty($criteria['name'])) {
            $query->andWhere(['LIKE', 'name', $criteria['name']]);
        }

        if (!empty($criteria['email'])) {
            $query->andWhere(['LIKE', 'email', $criteria['email']]);
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $query->andWhere(['IN', 'status', $criteria['status']]);
        }

        if (!empty($criteria['createdBetween'])) {
            $query->andWhere([
                'BETWEEN',
                'created_at',
                $criteria['createdBetween']['start'],
                $criteria['createdBetween']['end'],
            ]);
        }

        try {
            return $query->orderBy(['created_at' => SORT_DESC])->all();
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
