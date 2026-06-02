<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Delete user with cascade" operation.
 */
final class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Delete user with cascade to related entities.
     *
     * @param int $userId
     * @param bool $cascade
     * @return bool
     * @throws RuntimeException
     */
    public static function deleteUser(int $userId, bool $cascade = true): bool
    {
        $user = static::find($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        try {
            if ($cascade) {
                $user->posts()->delete();
                $user->roles()->detach();
            }

            return $user->delete();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new RuntimeException("User with ID {$userId} not found");
        } catch (\Exception $e) {
            throw new RuntimeException(
                'Failed to delete user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Force delete user with cascade.
     *
     * @param int $userId
     * @return bool
     */
    public static function forceDeleteUser(int $userId): bool
    {
        $user = static::withTrashed()->find($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        \DB::beginTransaction();

        try {
            $user->posts()->delete();
            $user->roles()->detach();
            $user->forceDelete();

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new RuntimeException(
                'Failed to force delete user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Batch delete users.
     *
     * @param array<int> $userIds
     * @param bool $cascade
     * @return int
     */
    public static function deleteUsers(array $userIds, bool $cascade = true): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;

        \DB::beginTransaction();

        try {
            $users = static::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                if ($cascade) {
                    $user->posts()->delete();
                    $user->roles()->detach();
                }

                $user->delete();
                $count++;
            }

            \DB::commit();

            return $count;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw new RuntimeException(
                'Failed to delete users: ' . $e->getMessage(),
                0,
                $e
            );
        }
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
