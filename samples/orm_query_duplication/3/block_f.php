<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * User repository using CakePHP ORM.
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Update user with version checking.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion
     * @return UserEntity
     * @throws RuntimeException
     */
    public function updateUser(int $userId, array $data, int $expectedVersion): UserEntity
    {
        $connection = $this->table->getConnection();
        $connection->begin();

        try {
            $user = $this->table->get($userId);

            if ($user->version !== $expectedVersion) {
                throw new RuntimeException(
                    'Version mismatch: user was modified by another process',
                    409
                );
            }

            $patchData = [];

            if (isset($data['name'])) {
                $patchData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $patchData['email'] = mb_strtolower(trim($data['email']));
            }

            $patchData['version'] = $expectedVersion + 1;

            $user = $this->table->patchEntity($user, $patchData);
            $this->table->saveOrFail($user);

            $connection->commit();

            return $user;
        } catch (RecordNotFoundException $e) {
            $connection->rollBack();
            throw new RuntimeException("User with ID {$userId} not found", 404, $e);
        } catch (\Exception $e) {
            $connection->rollBack();
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
     * @return UserEntity
     */
    public function updateUserWithRetry(int $userId, array $data, int $maxRetries = 3): UserEntity
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $user = $this->table->get($userId);
                $version = $user->version;

                $patchData = ['version' => $version + 1];

                if (isset($data['name'])) {
                    $patchData['name'] = $data['name'];
                }

                if (isset($data['email'])) {
                    $patchData['email'] = mb_strtolower(trim($data['email']));
                }

                $user = $this->table->patchEntity($user, $patchData);
                $this->table->saveOrFail($user);

                return $user;
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw new RuntimeException(
                        'Update failed after ' . $maxRetries . ' attempts: ' . $e->getMessage(),
                        409,
                        $e
                    );
                }

                usleep(100000 * $attempts);
            }
        }

        throw new RuntimeException('Update failed: max retries exceeded');
    }
}
