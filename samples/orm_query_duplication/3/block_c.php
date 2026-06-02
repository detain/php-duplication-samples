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
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Update user with version checking.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion
     * @return UserPropel
     * @throws RuntimeException
     */
    public function updateUser(int $userId, array $data, int $expectedVersion): UserPropel
    {
        $user = UserQuery::create()->findPk($userId, $this->connection);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        if ($user->getVersion() !== $expectedVersion) {
            throw new RuntimeException(
                'Version mismatch: user was modified by another process',
                409
            );
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $user->setEmail(mb_strtolower(trim($data['email'])));
        }

        $user->setVersion($expectedVersion + 1);

        try {
            $user->save($this->connection);

            return $user;
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to update user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Update user with automatic retry.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $maxRetries
     * @return UserPropel
     */
    public function updateUserWithRetry(int $userId, array $data, int $maxRetries = 3): UserPropel
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $this->connection->beginTransaction();

                $user = UserQuery::create()->findPk($userId, $this->connection);

                if ($user === null) {
                    throw new RuntimeException("User with ID {$userId} not found");
                }

                if (isset($data['name'])) {
                    $user->setName($data['name']);
                }

                if (isset($data['email'])) {
                    $user->setEmail(mb_strtolower(trim($data['email'])));
                }

                $user->setVersion($user->getVersion() + 1);
                $user->save($this->connection);

                $this->connection->commit();

                return $user;
            } catch (\Exception $e) {
                $this->connection->rollBack();

                if (str_contains($e->getMessage(), 'Version mismatch') || $attempts + 1 >= $maxRetries) {
                    throw new RuntimeException(
                        'Update failed: ' . $e->getMessage(),
                        $e instanceof RuntimeException ? $e->getCode() : 0,
                        $e
                    );
                }

                $attempts++;
                usleep(100000 * $attempts);
            }
        }

        throw new RuntimeException('Update failed: max retries exceeded');
    }

    /**
     * Batch update with version checking.
     *
     * @param array<array{id: int, name?: string, email?: string, version: int}> $updates
     * @return array<UserPropel>
     */
    public function batchUpdate(array $updates): array
    {
        $results = [];
        $errors = [];

        $this->connection->beginTransaction();

        try {
            foreach ($updates as $index => $update) {
                if (!isset($update['id'], $update['version'])) {
                    $errors[] = "Missing id or version at index {$index}";
                    continue;
                }

                $user = UserQuery::create()
                    ->filterById($update['id'])
                    ->filterByVersion($update['version'])
                    ->findOne($this->connection);

                if ($user === null) {
                    $errors[] = "Version mismatch or user not found at index {$index}";
                    continue;
                }

                if (isset($update['name'])) {
                    $user->setName($update['name']);
                }

                if (isset($update['email'])) {
                    $user->setEmail(mb_strtolower(trim($update['email'])));
                }

                $user->setVersion($update['version'] + 1);
                $user->save($this->connection);

                $results[] = $user;
            }

            if (!empty($errors) && empty($results)) {
                $this->connection->rollBack();
                throw new RuntimeException('All updates failed: ' . implode('; ', $errors));
            }

            $this->connection->commit();

            return $results;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
