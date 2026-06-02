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
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

    /**
     * Update user with version checking.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion
     * @return User
     * @throws RuntimeException
     */
    public function updateUser(int $userId, array $data, int $expectedVersion): User
    {
        $bean = R::load('user', $userId);

        if ($bean->id === 0) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        if ((int) $bean->version !== $expectedVersion) {
            throw new RuntimeException(
                'Version mismatch: user was modified by another process',
                409
            );
        }

        if (isset($data['name'])) {
            $bean->name = $data['name'];
        }

        if (isset($data['email'])) {
            $bean->email = mb_strtolower(trim($data['email']));
        }

        $bean->version = $expectedVersion + 1;
        $bean->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');

        try {
            R::store($bean);

            return $this->mapToEntity($bean);
        } catch (RedException $e) {
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
     * @return User
     */
    public function updateUserWithRetry(int $userId, array $data, int $maxRetries = 3): User
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                R::begin();

                $bean = R::load('user', $userId);

                if ($bean->id === 0) {
                    throw new RuntimeException("User with ID {$userId} not found");
                }

                if (isset($data['name'])) {
                    $bean->name = $data['name'];
                }

                if (isset($data['email'])) {
                    $bean->email = mb_strtolower(trim($data['email']));
                }

                $bean->version = (int) $bean->version + 1;
                $bean->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');

                R::store($bean);
                R::commit();

                return $this->mapToEntity($bean);
            } catch (\Exception $e) {
                R::rollback();

                if ($attempts + 1 >= $maxRetries) {
                    throw new RuntimeException(
                        'Update failed: ' . $e->getMessage(),
                        0,
                        $e
                    );
                }

                $attempts++;
                usleep(100000 * $attempts);
            }
        }

        throw new RuntimeException('Update failed: max retries exceeded');
    }

    private function mapToEntity(\RedBeanPHP\OODBBean $bean): User
    {
        $user = new User();
        $user->id = (int) $bean->id;
        $user->email = $bean->email;
        $user->name = $bean->name ?? '';
        $user->password = $bean->password ?? '';
        $user->version = (int) $bean->version;
        $user->createdAt = isset($bean->createdAt)
            ? new \DateTime($bean->createdAt)
            : new \DateTime();

        return $user;
    }
}
