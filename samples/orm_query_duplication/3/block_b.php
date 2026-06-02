<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Update user with version checking (optimistic locking).
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion
     * @return User
     * @throws RuntimeException
     */
    public static function updateWithVersion(int $userId, array $data, int $expectedVersion): User
    {
        $user = static::find($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        if ($user->version !== $expectedVersion) {
            throw new RuntimeException(
                'Version mismatch: user was modified by another process',
                409
            );
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = mb_strtolower(trim($data['email']));
        }

        $user->version = $expectedVersion + 1;

        $result = $user->save();

        if (!$result) {
            throw new RuntimeException('Failed to update user');
        }

        return $user;
    }

    /**
     * Update user with automatic retry on version conflict.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $maxRetries
     * @return User
     */
    public static function updateWithRetry(int $userId, array $data, int $maxRetries = 3): User
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $user = static::lockForUpdate()->find($userId);

                if ($user === null) {
                    throw new RuntimeException("User with ID {$userId} not found");
                }

                if (isset($data['name'])) {
                    $user->name = $data['name'];
                }

                if (isset($data['email'])) {
                    $user->email = mb_strtolower(trim($data['email']));
                }

                if (!$user->save()) {
                    throw new RuntimeException('Failed to update user');
                }

                return $user;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw new RuntimeException("User with ID {$userId} not found");
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw new RuntimeException(
                        'Update failed after ' . $maxRetries . ' attempts: ' . $e->getMessage(),
                        409,
                        $e
                    );
                }

                usleep(100000 * $attempts);
            }
        }

        throw new RuntimeException('Update failed: max retries exceeded');
    }

    /**
     * Batch update users with version tracking.
     *
     * @param array<array{id: int, name?: string, email?: string, version: int}> $updates
     * @return array<User>
     */
    public static function batchUpdateWithVersion(array $updates): array
    {
        $results = [];
        $errors = [];

        \DB::beginTransaction();

        try {
            foreach ($updates as $index => $update) {
                if (!isset($update['id'], $update['version'])) {
                    $errors[] = "Missing id or version at index {$index}";
                    continue;
                }

                try {
                    $user = static::where('id', $update['id'])
                        ->where('version', $update['version'])
                        ->lockForUpdate()
                        ->first();

                    if ($user === null) {
                        $errors[] = "Version mismatch or user not found at index {$index}";
                        continue;
                    }

                    if (isset($update['name'])) {
                        $user->name = $update['name'];
                    }

                    if (isset($update['email'])) {
                        $user->email = mb_strtolower(trim($update['email']));
                    }

                    $user->version = $update['version'] + 1;
                    $user->save();

                    $results[] = $user;
                } catch (\Exception $e) {
                    $errors[] = "Failed to update at index {$index}: " . $e->getMessage();
                }
            }

            if (!empty($errors) && empty($results)) {
                \DB::rollBack();
                throw new RuntimeException('All updates failed: ' . implode('; ', $errors));
            }

            \DB::commit();

            return $results;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
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
}
