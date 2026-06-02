<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use App\Model\Post;
use App\Model\PostQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User repository using Propel ORM.
 * Demonstrates "Delete user with cascade" operation.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Delete user with cascade to related entities.
     *
     * @param int $userId
     * @param bool $cascade
     * @return bool
     * @throws RuntimeException
     */
    public function deleteUser(int $userId, bool $cascade = true): bool
    {
        $user = UserQuery::create()->findPk($userId, $this->connection);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $this->connection->beginTransaction();

        try {
            if ($cascade) {
                PostQuery::create()
                    ->filterByUserId($userId)
                    ->deleteAll($this->connection);

                $user->getUserRoles()->deleteAll($this->connection);
            }

            $user->delete($this->connection);

            $this->connection->commit();

            return true;
        } catch (PropelException $e) {
            $this->connection->rollBack();
            throw new RuntimeException(
                'Failed to delete user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Soft delete user.
     *
     * @param int $userId
     * @return UserPropel
     */
    public function softDeleteUser(int $userId): UserPropel
    {
        $user = UserQuery::create()->findPk($userId, $this->connection);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $user->setDeletedAt(time());

        try {
            $user->save($this->connection);

            return $user;
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to soft delete user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Batch delete users.
     *
     * @param array<int> $userIds
     * @return int
     */
    public function deleteUsers(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;

        $this->connection->beginTransaction();

        try {
            foreach ($userIds as $userId) {
                $user = UserQuery::create()->findPk($userId, $this->connection);

                if ($user !== null) {
                    $user->delete($this->connection);
                    $count++;
                }
            }

            $this->connection->commit();

            return $count;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw new RuntimeException(
                'Failed to delete users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
