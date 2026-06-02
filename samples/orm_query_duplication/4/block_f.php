<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Delete user with cascade" operation.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Delete user with cascade.
     *
     * @param int $userId
     * @param bool $cascade
     * @return bool
     * @throws RuntimeException
     */
    public function deleteUser(int $userId, bool $cascade = true): bool
    {
        $connection = $this->table->getConnection();
        $connection->begin();

        try {
            $user = $this->table->get($userId);

            if ($cascade) {
                $this->table->Posts->deleteAll(['author_id' => $userId]);
                $this->table->UserRoles->deleteAll(['user_id' => $userId]);
            }

            $result = $this->table->delete($user);

            $connection->commit();

            return $result;
        } catch (RecordNotFoundException $e) {
            $connection->rollBack();
            throw new RuntimeException("User with ID {$userId} not found", 404, $e);
        } catch (\Exception $e) {
            $connection->rollBack();
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
     * @return UserEntity
     */
    public function softDeleteUser(int $userId): UserEntity
    {
        $user = $this->table->get($userId);

        $user->deletedAt = new \DateTime();

        try {
            $this->table->saveOrFail($user);

            return $user;
        } catch (\Exception $e) {
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
     * @param bool $cascade
     * @return int
     */
    public function deleteUsers(array $userIds, bool $cascade = true): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $connection = $this->table->getConnection();
        $connection->begin();

        try {
            if ($cascade) {
                $this->table->Posts->deleteAll(['author_id IN' => $userIds]);
                $this->table->UserRoles->deleteAll(['user_id IN' => $userIds]);
            }

            $count = $this->table->deleteAll(['id IN' => $userIds]);

            $connection->commit();

            return $count;
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new RuntimeException(
                'Failed to delete users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
