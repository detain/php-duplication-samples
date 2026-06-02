<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use App\Entity\User;
use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * User repository using RedBeanPHP ORM.
 * Demonstrates "Delete user with cascade" operation.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
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
        $bean = R::load('user', $userId);

        if ($bean->id === 0) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        R::begin();

        try {
            if ($cascade) {
                R::findAndDelete('post', 'author_id = ?', [$userId]);
                R::findAndDelete('user_role', 'user_id = ?', [$userId]);
            }

            R::trash($bean);
            R::commit();

            return true;
        } catch (RedException $e) {
            R::rollback();
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
     * @return User
     */
    public function softDeleteUser(int $userId): User
    {
        $bean = R::load('user', $userId);

        if ($bean->id === 0) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $bean->deletedAt = (new \DateTime())->format('Y-m-d H:i:s');

        try {
            R::store($bean);

            return $this->mapToEntity($bean);
        } catch (RedException $e) {
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

        $count = 0;

        R::begin();

        try {
            foreach ($userIds as $userId) {
                $bean = R::load('user', $userId);

                if ($bean->id !== 0) {
                    if ($cascade) {
                        R::findAndDelete('post', 'author_id = ?', [$userId]);
                        R::findAndDelete('user_role', 'user_id = ?', [$userId]);
                    }

                    R::trash($bean);
                    $count++;
                }
            }

            R::commit();

            return $count;
        } catch (\Exception $e) {
            R::rollback();
            throw new RuntimeException(
                'Failed to delete users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function mapToEntity(\RedBeanPHP\OODBBean $bean): User
    {
        $user = new User();
        $user->id = (int) $bean->id;
        $user->email = $bean->email;
        $user->name = $bean->name ?? '';
        $user->createdAt = isset($bean->createdAt)
            ? new \DateTime($bean->createdAt)
            : new \DateTime();

        return $user;
    }
}
