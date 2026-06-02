<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Search users with WHERE clause" operation.
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
     * Search users with multiple WHERE conditions.
     *
     * @param array{name?: string, email?: string, status?: string} $criteria
     * @param int $limit
     * @param int $offset
     * @return Collection<int, User>
     */
    public static function searchUsers(array $criteria, int $limit = 20, int $offset = 0): Collection
    {
        $query = static::query();

        if (!empty($criteria['name'])) {
            $query->where('name', 'LIKE', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['email'])) {
            $query->where('email', 'LIKE', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['createdAfter'])) {
            $query->where('created_at', '>=', $criteria['createdAfter']);
        }

        return $query->orderBy('created_at', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Search users with OR conditions.
     *
     * @param array<string, string> $criteria
     * @return Collection<int, User>
     */
    public static function searchUsersOr(array $criteria): Collection
    {
        $query = static::query();

        $query->where(function ($q) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($key === 'name' || $key === 'email') {
                    $q->orWhere($key, 'LIKE', '%' . $value . '%');
                }
            }
        });

        return $query->orderBy('created_at', 'DESC')->get();
    }

    /**
     * Find users by status with count.
     *
     * @param string $status
     * @return array{users: Collection, count: int}
     */
    public static function findByStatus(string $status): array
    {
        $query = static::where('status', $status);

        return [
            'users' => $query->orderBy('created_at', 'DESC')->get(),
            'count' => $query->count(),
        ];
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
     * @return Collection<int, User>
     */
    public static function advancedSearch(array $criteria): Collection
    {
        $query = static::query();

        if (!empty($criteria['name'])) {
            $query->where('name', 'LIKE', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['email'])) {
            $query->where('email', 'LIKE', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $query->whereIn('status', $criteria['status']);
        }

        if (!empty($criteria['createdBetween'])) {
            $query->whereBetween('created_at', [
                $criteria['createdBetween']['start'],
                $criteria['createdBetween']['end'],
            ]);
        }

        if (!empty($criteria['roleIds'])) {
            $query->whereHas('roles', function ($q) use ($criteria) {
                $q->whereIn('roles.id', $criteria['roleIds']);
            });
        }

        return $query->orderBy('created_at', 'DESC')->get();
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

    /**
     * Get roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
