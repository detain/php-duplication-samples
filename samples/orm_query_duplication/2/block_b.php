<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Create new user with auto-generated ID" operation.
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
     * Create a new user and return the model with generated ID.
     *
     * @param array{name: string, email: string, password: string} $attributes
     * @return User
     * @throws RuntimeException If creation fails
     */
    public static function createUser(array $attributes): User
    {
        $validated = self::validateUserData($attributes);

        $normalizedEmail = mb_strtolower(trim($validated['email']));

        if (self::where('email', $normalizedEmail)->exists()) {
            throw new RuntimeException("User with email '{$normalizedEmail}' already exists");
        }

        try {
            $user = static::create([
                'name' => $validated['name'],
                'email' => $normalizedEmail,
                'password' => password_hash($validated['password'], PASSWORD_ARGON2ID),
                'email_verified_at' => null,
                'remember_token' => null,
            ]);

            return $user;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new RuntimeException('Failed to create user: Record not found', 0, $e);
        } catch (\Illuminate\Database\QueryException $e) {
            throw new RuntimeException(
                'Failed to create user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create multiple users in a batch.
     *
     * @param array<array{name: string, email: string, password: string}> $users
     * @return Collection<int, User>
     */
    public static function createUsersBatch(array $users): Collection
    {
        if (empty($users)) {
            return new Collection();
        }

        $createdUsers = new Collection();
        $batchSize = 50;

        foreach ($users as $index => $data) {
            $validated = self::validateUserData($data);
            $normalizedEmail = mb_strtolower(trim($validated['email']));

            if (self::where('email', $normalizedEmail)->exists()) {
                throw new RuntimeException(
                    "User with email '{$normalizedEmail}' already exists at index {$index}"
                );
            }

            $createdUsers->push(static::create([
                'name' => $validated['name'],
                'email' => $normalizedEmail,
                'password' => password_hash($validated['password'], PASSWORD_ARGON2ID),
            ]));

            if (($index + 1) % $batchSize === 0 && $index + 1 < count($users)) {
                static::query()->update(['updated_at' => now()]);
            }
        }

        return $createdUsers;
    }

    /**
     * Create user with role assignment.
     *
     * @param array{name: string, email: string, password: string} $data
     * @param array<int> $roleIds
     * @return User
     */
    public static function createUserWithRoles(array $data, array $roleIds = []): User
    {
        $user = self::createUser($data);

        if (!empty($roleIds)) {
            $user->roles()->sync($roleIds);
        }

        return $user->fresh(['roles']);
    }

    /**
     * Create or get existing user by email.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return User
     */
    public static function findOrCreateUser(array $data): User
    {
        $validated = self::validateUserData($data);
        $normalizedEmail = mb_strtolower(trim($validated['email']));

        return static::firstOrCreate(
            ['email' => $normalizedEmail],
            [
                'name' => $validated['name'],
                'password' => password_hash($validated['password'], PASSWORD_ARGON2ID),
            ]
        );
    }

    /**
     * Validate user data before creation.
     *
     * @param array $data
     * @return array
     * @throws RuntimeException
     */
    private static function validateUserData(array $data): array
    {
        if (empty($data['name'])) {
            throw new RuntimeException('User name is required');
        }

        if (empty($data['email'])) {
            throw new RuntimeException('User email is required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email format');
        }

        if (empty($data['password'])) {
            throw new RuntimeException('User password is required');
        }

        if (strlen($data['password']) < 8) {
            throw new RuntimeException('Password must be at least 8 characters');
        }

        return $data;
    }

    /**
     * Get the user's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * Get the user's roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}
