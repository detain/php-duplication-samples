<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User repository using Propel ORM.
 * Demonstrates "Batch insert users" operation for bulk data imports.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Batch insert users efficiently.
     *
     * @param array<array{name: string, email: string, password: string, status?: string}> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array<UserPropel>
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

        $this->connection->beginTransaction();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);

                foreach ($batch as $data) {
                    $this->validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));
                    $this->ensureEmailUniqueness($normalizedEmail);

                    $user = new UserPropel();
                    $user->setName($data['name']);
                    $user->setEmail($normalizedEmail);
                    $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                    $user->setStatus($data['status'] ?? 'active');
                    $user->setCreatedAt(time());

                    $user->save($this->connection);
                    $createdUsers[] = $user;
                }

                $processed += count($batch);

                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }
            }

            $this->connection->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $this->connection->rollBack();
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
     * @return array<UserPropel>
     */
    public function bulkUpsertUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $results = [];

        $this->connection->beginTransaction();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                if (isset($data['id'])) {
                    $user = UserQuery::create()->findPk($data['id'], $this->connection);

                    if ($user !== null) {
                        $user->setName($data['name']);
                        $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                        $user->setUpdatedAt(time());
                        $user->save($this->connection);
                        $results[] = $user;
                        continue;
                    }
                }

                $existingUser = UserQuery::create()
                    ->filterByEmail($normalizedEmail)
                    ->findOne($this->connection);

                if ($existingUser !== null) {
                    $existingUser->setName($data['name']);
                    $existingUser->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                    $existingUser->setUpdatedAt(time());
                    $existingUser->save($this->connection);
                    $results[] = $existingUser;
                } else {
                    $user = new UserPropel();
                    $user->setName($data['name']);
                    $user->setEmail($normalizedEmail);
                    $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                    $user->setCreatedAt(time());
                    $user->save($this->connection);
                    $results[] = $user;
                }
            }

            $this->connection->commit();

            return $results;
        } catch (\Exception $e) {
            $this->connection->rollBack();
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
