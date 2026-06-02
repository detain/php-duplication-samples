<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
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
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return User|null
     */
    public static function getUserWithRelations(int $userId): ?User
    {
        return static::with(['posts', 'roles'])
            ->find($userId);
    }

    /**
     * Get users with their post counts.
     *
     * @param int $limit
     * @return array<array{user: User, postCount: int}>
     */
    public static function getUsersWithPostCounts(int $limit = 100): array
    {
        $users = static::withCount('posts')
            ->orderBy('posts_count', 'DESC')
            ->limit($limit)
            ->get();

        return $users->map(fn($user) => [
            'user' => $user,
            'postCount' => $user->posts_count,
        ])->toArray();
    }

    /**
     * Get users with their latest post.
     *
     * @param int $limit
     * @return array<array{user: User, latestPost: Post|null}>
     */
    public static function getUsersWithLatestPost(int $limit = 100): array
    {
        $users = static::with(['posts' => function ($query) {
            $query->latest()->limit(1);
        }])
            ->limit($limit)
            ->get();

        return $users->map(fn($user) => [
            'user' => $user,
            'latestPost' => $user->posts->first(),
        ])->toArray();
    }

    /**
     * Get users grouped by role.
     *
     * @return array<array{role: Role, users: Collection}>
     */
    public static function getUsersGroupedByRole(): array
    {
        $roles = Role::with('users')->get();

        return $roles->map(fn($role) => [
            'role' => $role,
            'users' => $role->users,
        ])->toArray();
    }

    /**
     * Get users who have posts in specific categories.
     *
     * @param array<int> $categoryIds
     * @return Collection<int, User>
     */
    public static function getUsersWithPostsInCategories(array $categoryIds): Collection
    {
        if (empty($categoryIds)) {
            return new Collection();
        }

        return static::whereHas('posts', function ($query) use ($categoryIds) {
            $query->whereIn('category_id', $categoryIds);
        })
            ->orderBy('name', 'ASC')
            ->get();
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
