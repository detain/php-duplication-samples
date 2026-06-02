<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * User model using Eloquent ORM (Laravel).
 * Demonstrates "Find user by email" operation in Eloquent style.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property \DateTime|null $email_verified_at
 * @property string $remember_token
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property Collection|null $posts
 * @property Collection|null $roles
 */
final class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     * @return User|null The found user or null if not exists
     * @throws RuntimeException If query execution fails
     */
    public static function findByEmail(string $email): ?User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return static::where('email', $normalizedEmail)->first();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return null;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email or fail with an exception.
     *
     * @param string $email The email to search for
     * @return User The found user
     * @throws RuntimeException If user not found or query fails
     */
    public static function findByEmailOrFail(string $email): User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $user = static::where('email', $normalizedEmail)->firstOrFail();
            return $user;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new RuntimeException(
                "User with email '{$normalizedEmail}' not found",
                404,
                $e
            );
        } catch (\Illuminate\Database\QueryException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email with eager loaded relations.
     *
     * @param string $email The email to search for
     * @param array<string> $relations Relations to eager load
     * @return User|null
     */
    public static function findByEmailWith(string $email, array $relations = []): ?User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return static::with($relations)
                ->where('email', $normalizedEmail)
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            throw new RuntimeException(
                'Failed to find user by email with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if a user exists with the given email.
     *
     * @param string $email The email to check
     * @return bool True if user exists
     */
    public static function existsByEmail(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return static::where('email', $normalizedEmail)->exists();
        } catch (\Illuminate\Database\QueryException $e) {
            throw new RuntimeException(
                'Failed to check user existence: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users by email domain.
     *
     * @param string $domain The email domain to search for
     * @return Collection<int, User>
     */
    public static function findByEmailDomain(string $domain): Collection
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        $pattern = "%@{$normalizedDomain}";

        try {
            return static::where('email', 'LIKE', $pattern)->get();
        } catch (\Illuminate\Database\QueryException $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get the user's posts relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * Get the user's roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
