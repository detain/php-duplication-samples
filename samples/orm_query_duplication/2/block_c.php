<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User service using Propel ORM.
 * Demonstrates "Create new user with auto-generated ID" operation.
 */
final class PropelUserService
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Create a new user and return the model with generated ID.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserPropel
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): UserPropel
    {
        $this->validateUserData($data);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        $this->ensureEmailUniqueness($normalizedEmail);

        $user = new UserPropel();
        $user->setName($data['name']);
        $user->setEmail($normalizedEmail);
        $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
        $user->setCreatedAt(time());
        $user->setUpdatedAt(time());

        try {
            $user->save($this->connection);

            return $user;
        } catch (PropelException $e) {
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
     * @return array<UserPropel>
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $batchSize = 50;

        $this->connection->beginTransaction();

        try {
            foreach ($users as $index => $data) {
                $this->validateUserData($data);
                $normalizedEmail = mb_strtolower(trim($data['email']));
                $this->ensureEmailUniqueness($normalizedEmail);

                $user = new UserPropel();
                $user->setName($data['name']);
                $user->setEmail($normalizedEmail);
                $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                $user->setCreatedAt(time());
                $user->setUpdatedAt(time());

                $user->save($this->connection);
                $createdUsers[] = $user;

                if (($index + 1) % $batchSize === 0) {
                    $this->connection->commit();
                    $this->connection->beginTransaction();
                }
            }

            $this->connection->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw new RuntimeException(
                'Failed to create users batch: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create user and assign roles.
     *
     * @param array{name: string, email: string, password: string} $data
     * @param array<int> $roleIds
     * @return UserPropel
     */
    public function createUserWithRoles(array $data, array $roleIds = []): UserPropel
    {
        $user = $this->createUser($data);

        if (!empty($roleIds)) {
            foreach ($roleIds as $roleId) {
                $user->addUserRole($roleId);
            }
            $user->save($this->connection);
        }

        return $user;
    }

    /**
     * Find or create user by email.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserPropel
     */
    public function findOrCreateUser(array $data): UserPropel
    {
        $this->validateUserData($data);
        $normalizedEmail = mb_strtolower(trim($data['email']));

        $existing = UserQuery::create()
            ->filterByEmail($normalizedEmail)
            ->findOne($this->connection);

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
        $existing = UserQuery::create()
            ->filterByEmail($email)
            ->findOne($this->connection);

        if ($existing !== null) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
