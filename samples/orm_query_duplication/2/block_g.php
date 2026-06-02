<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use App\Entity\User;
use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * User service using RedBeanPHP ORM.
 * Demonstrates "Create new user with auto-generated ID" operation.
 */
final class RedBeanUserService
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

    /**
     * Create a new user and return the entity with generated ID.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return User
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): User
    {
        $this->validateUserData($data);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        $this->ensureEmailUniqueness($normalizedEmail);

        try {
            $bean = R::dispense('user');
            $bean->name = $data['name'];
            $bean->email = $normalizedEmail;
            $bean->password = password_hash($data['password'], PASSWORD_ARGON2ID);
            $bean->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
            $bean->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');

            R::store($bean);

            return $this->mapToEntity($bean);
        } catch (RedException $e) {
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
     * @return array<User>
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $batchSize = 50;

        R::begin();

        try {
            foreach ($users as $index => $data) {
                $this->validateUserData($data);
                $normalizedEmail = mb_strtolower(trim($data['email']));
                $this->ensureEmailUniqueness($normalizedEmail);

                $bean = R::dispense('user');
                $bean->name = $data['name'];
                $bean->email = $normalizedEmail;
                $bean->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                $bean->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
                $bean->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');

                R::store($bean);
                $createdUsers[] = $this->mapToEntity($bean);

                if (($index + 1) % $batchSize === 0) {
                    R::commit();
                    R::begin();
                }
            }

            R::commit();

            return $createdUsers;
        } catch (\Exception $e) {
            R::rollback();
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
     * @return User
     */
    public function createUserWithRoles(array $data, array $roleIds = []): User
    {
        $user = $this->createUser($data);

        if (!empty($roleIds)) {
            foreach ($roleIds as $roleId) {
                R::associate($user->id, $roleId);
            }
        }

        return $user;
    }

    /**
     * Find or create user by email.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return User
     */
    public function findOrCreateUser(array $data): User
    {
        $this->validateUserData($data);
        $normalizedEmail = mb_strtolower(trim($data['email']));

        $bean = R::findOne('user', 'email = ?', [$normalizedEmail]);

        if ($bean !== null) {
            return $this->mapToEntity($bean);
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
        $existing = R::findOne('user', 'email = ?', [$email]);

        if ($existing !== null) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }

    private function mapToEntity(\RedBeanPHP\OODBBean $bean): User
    {
        $user = new User();
        $user->id = (int) $bean->id;
        $user->email = $bean->email;
        $user->name = $bean->name ?? '';
        $user->password = $bean->password ?? '';
        $user->createdAt = isset($bean->createdAt)
            ? new \DateTime($bean->createdAt)
            : new \DateTime();

        return $user;
    }
}
