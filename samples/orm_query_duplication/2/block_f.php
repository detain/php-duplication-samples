<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * User service using CakePHP ORM.
 * Demonstrates "Create new user with auto-generated ID" operation.
 */
final class CakeUserService
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Create a new user and return the entity with generated ID.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserEntity
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): UserEntity
    {
        $this->validateUserData($data);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        $this->ensureEmailUniqueness($normalizedEmail);

        $user = $this->table->newEntity([
            'name' => $data['name'],
            'email' => $normalizedEmail,
            'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
        ]);

        try {
            $result = $this->table->saveOrFail($user);

            return $result;
        } catch (\Cake\Database\Exception\DatabaseException $e) {
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
     * @return array<UserEntity>
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $batchSize = 50;

        $connection = $this->table->getConnection();
        $connection->begin();

        try {
            foreach ($users as $index => $data) {
                $this->validateUserData($data);
                $normalizedEmail = mb_strtolower(trim($data['email']));
                $this->ensureEmailUniqueness($normalizedEmail);

                $user = $this->table->newEntity([
                    'name' => $data['name'],
                    'email' => $normalizedEmail,
                    'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                ]);

                $savedUser = $this->table->saveOrFail($user);
                $createdUsers[] = $savedUser;

                if (($index + 1) % $batchSize === 0 && $index + 1 < count($users)) {
                    $connection->commit();
                    $connection->begin();
                }
            }

            $connection->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new RuntimeException(
                'Failed to create users batch: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create user with role assignments.
     *
     * @param array{name: string, email: string, password: string} $data
     * @param array<int> $roleIds
     * @return UserEntity
     */
    public function createUserWithRoles(array $data, array $roleIds = []): UserEntity
    {
        $user = $this->createUser($data);

        if (!empty($roleIds)) {
            $user->_joinData = [];
            foreach ($roleIds as $roleId) {
                $user->_joinData[] = [
                    'role_id' => $roleId,
                    'user_id' => $user->id,
                ];
            }

            $this->table->saveOrFail($user);
        }

        return $user;
    }

    /**
     * Find or create user by email.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserEntity
     */
    public function findOrCreateUser(array $data): UserEntity
    {
        $this->validateUserData($data);
        $normalizedEmail = mb_strtolower(trim($data['email']));

        $existing = $this->table->find()
            ->where(['email' => $normalizedEmail])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->createUser($data);
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

    private function ensureEmailUniqueness(string $email): void
    {
        $existing = $this->table->exists(['email' => $email]);

        if ($existing) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
