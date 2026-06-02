<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Batch insert users" operation for bulk data imports.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Batch insert users efficiently.
     *
     * @param array<array{name: string, email: string, password: string, status?: string}> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array<UserEntity>
     */
    public function batchInsertUsers(
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

        $connection = $this->table->getConnection();
        $connection->begin();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);
                $entities = [];

                foreach ($batch as $data) {
                    $this->validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));

                    if ($this->table->exists(['email' => $normalizedEmail])) {
                        throw new RuntimeException("User with email '{$normalizedEmail}' already exists");
                    }

                    $entities[] = $this->table->newEntity([
                        'name' => $data['name'],
                        'email' => $normalizedEmail,
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                        'status' => $data['status'] ?? 'active',
                    ]);
                }

                $savedEntities = $this->table->saveMany($entities);
                $createdUsers = array_merge($createdUsers, $savedEntities);

                $processed += count($batch);

                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }
            }

            $connection->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new RuntimeException(
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
     * @return array<UserEntity>
     */
    public function bulkUpsertUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $results = [];

        $connection = $this->table->getConnection();
        $connection->begin();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                if (isset($data['id'])) {
                    $user = $this->table->get($data['id']);

                    $user = $this->table->patchEntity($user, [
                        'name' => $data['name'],
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                    ]);

                    $this->table->saveOrFail($user);
                    $results[] = $user;
                    continue;
                }

                $existingUser = $this->table->find()
                    ->where(['email' => $normalizedEmail])
                    ->first();

                if ($existingUser !== null) {
                    $existingUser = $this->table->patchEntity($existingUser, [
                        'name' => $data['name'],
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                    ]);

                    $this->table->saveOrFail($existingUser);
                    $results[] = $existingUser;
                } else {
                    $user = $this->table->newEntity([
                        'name' => $data['name'],
                        'email' => $normalizedEmail,
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                    ]);

                    $this->table->saveOrFail($user);
                    $results[] = $user;
                }
            }

            $connection->commit();

            return $results;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new RuntimeException(
                'Failed to bulk upsert users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function validateUserData(array $data): void
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
    }
}
