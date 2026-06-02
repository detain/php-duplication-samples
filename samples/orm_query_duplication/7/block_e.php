<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\data\Pagination;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Pagination" operation.
 */
final class YiiUserRepository
{
    /**
     * Get paginated users.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return array{users: array<UserYii>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $query = UserYii::find();

        if (!empty($filters['status'])) {
            $query->andWhere(['status' => $filters['status']]);
        }

        if (!empty($filters['search'])) {
            $query->andWhere(['or',
                ['like', 'name', $filters['search']],
                ['like', 'email', $filters['search']],
            ]);
        }

        try {
            $total = $query->count();
            $pagination = new Pagination([
                'totalCount' => $total,
                'page' => $page - 1,
                'pageSize' => $perPage,
            ]);

            $users = $query->orderBy(['created_at' => SORT_DESC])
                ->offset($pagination->offset)
                ->limit($pagination->limit)
                ->all();

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $pagination->getPageCount(),
            ];
        } catch (Exception $e) {
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
     * @return array{users: array<UserYii>, nextCursor: int|null, hasMore: bool}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        try {
            $query = UserYii::find()
                ->where(['>', 'id', $cursor])
                ->orderBy(['id' => SORT_ASC])
                ->limit($limit + 1);

            $users = $query->all();
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
        } catch (Exception $e) {
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
     * @return array{users: array<UserYii>, total: int, page: int, perPage: int, totalPages: int}
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
            $total = UserYii::find()->joinWith('posts')->groupBy('id')->count();

            $users = UserYii::find()
                ->select([
                    'user.*',
                    'COUNT(post.id) as postCount',
                ])
                ->joinWith('posts')
                ->groupBy('user.id')
                ->orderBy(['postCount' => SORT_DESC, 'created_at' => SORT_DESC])
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->all();

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($total / $perPage),
            ];
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
