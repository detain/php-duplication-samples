<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Pagination" operation.
 */
final class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * Get paginated users.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return array{users: Collection, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $query = static::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        $total = $query->count();

        $users = $query->orderBy('created_at', 'DESC')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

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
     * @return array{users: Collection, nextCursor: int|null, hasMore: bool}
     */
    public static function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        $query = static::where('id', '>', $cursor)
            ->orderBy('id', 'ASC')
            ->limit($limit + 1);

        $users = $query->get();

        $hasMore = $users->count() > $limit;

        if ($hasMore) {
            $users = $users->slice(0, $limit);
        }

        $nextCursor = $users->isNotEmpty() ? $users->last()->id : null;

        return [
            'users' => $users->values(),
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore,
        ];
    }

    /**
     * Get paginated users sorted by activity.
     *
     * @param int $page
     * @param int $perPage
     * @return array{users: Collection, total: int, page: int, perPage: int, totalPages: int}
     */
    public static function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $total = static::has('posts')->count();

        $users = static::withCount('posts')
            ->has('posts')
            ->orderBy('posts_count', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

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
     * Get posts relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
