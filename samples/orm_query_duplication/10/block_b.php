<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * User model using Eloquent ORM.
 * Demonstrates "Batch insert users" operation for bulk data imports.
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
     * Batch insert users efficiently.
     *
     * @param array<array{name: string, email: string, password: string, status?: string}> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array<User>
     */
    public static function batchInsertUsers(
        array $users,
        int $batchSize = 100,
        ?callable $progressCallback = null
    ): array {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $total = count($users);
        $processed = 0;

        DB::beginTransaction();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);
                $insertBatch = [];

                foreach ($batch as $data) {
                    static::validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));

                    if (static::where('email', $normalizedEmail)->exists()) {
                        throw new \RuntimeException("User with email '{$normalizedEmail}' already exists");
                    }

                    $insertBatch[] = [
                        'name' => $data['name'],
                        'email' => $normalizedEmail,
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                        'status' => $data['status'] ?? 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                static::insert($insertBatch);

                $emails = array_column($insertBatch, 'email');
                $insertedUsers = static::whereIn('email', $emails)->get();
                $createdUsers = array_merge($createdUsers, $insertedUsers->all());

                $processed += count($batch);

                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }
            }

            DB::commit();

            return $createdUsers;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \RuntimeException(
                'Failed to batch insert users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Bulk upsert users.
     *
     * @param array<array{id?: int, name: string, email: string, password: string}> $users
     * @return array<User>
     */
    public static function bulkUpsertUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $results = [];

        DB::beginTransaction();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                if (isset($data['id'])) {
                    $user = static::find($data['id']);

                    if ($user !== null) {
                        $user->update([
                            'name' => $data['name'],
                            'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                        ]);
                        $results[] = $user;
                        continue;
                    }
                }

                $user = static::where('email', $normalizedEmail)->first();

                if ($user !== null) {
                    $user->update([
                        'name' => $data['name'],
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                    ]);
                    $results[] = $user;
                } else {
                    $user = static::create([
                        'name' => $data['name'],
                        'email' => $normalizedEmail,
                        'password' => $data['password'],
                    ]);
                    $results[] = $user;
                }
            }

            DB::commit();

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \RuntimeException(
                'Failed to bulk upsert users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Import users from CSV data.
     *
     * @param array<array{0: string, 1: string, 2: string}> $csvData
     * @param int $batchSize
     * @return array{imported: int, failed: int, errors: array<string>}
     */
    public static function importUsersFromCsv(array $csvData, int $batchSize = 100): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            for ($i = 0; $i < count($csvData); $i += $batchSize) {
                $batch = array_slice($csvData, $i, $batchSize);
                $insertBatch = [];

                foreach ($batch as $rowIndex => $row) {
                    $rowNumber = $i + $rowIndex + 2;

                    if (count($row) < 3) {
                        $errors[] = "Row {$rowNumber}: Invalid format, expected 3 columns";
                        $failed++;
                        continue;
                    }

                    [$name, $email, $password] = $row;

                    try {
                        $data = [
                            'name' => trim($name),
                            'email' => trim($email),
                            'password' => trim($password),
                        ];

                        static::validateUserData($data);

                        $normalizedEmail = mb_strtolower($data['email']);

                        if (static::where('email', $normalizedEmail)->exists()) {
                            throw new \RuntimeException("Email '{$normalizedEmail}' already exists");
                        }

                        $insertBatch[] = [
                            'name' => $data['name'],
                            'email' => $normalizedEmail,
                            'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                        $failed++;
                    }
                }

                if (!empty($insertBatch)) {
                    static::insert($insertBatch);
                }
            }

            DB::commit();

            return [
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \RuntimeException(
                'Failed to import users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private static function validateUserData(array $data): void
    {
        if (empty($data['name'])) {
            throw new \RuntimeException('User name is required');
        }

        if (empty($data['email'])) {
            throw new \RuntimeException('User email is required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email format: ' . $data['email']);
        }

        if (empty($data['password'])) {
            throw new \RuntimeException('User password is required');
        }

        if (strlen($data['password']) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters');
        }
    }
}
