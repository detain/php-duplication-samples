<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class YiiUserRepository
{
    /**
     * Update user with version checking.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion
     * @return UserYii
     * @throws RuntimeException
     */
    public function updateUser(int $userId, array $data, int $expectedVersion): UserYii
    {
        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            $user = UserYii::find()
                ->where(['id' => $userId, 'version' => $expectedVersion])
                ->lockForUpdate()
                ->one();

            if ($user === null) {
                throw new RuntimeException(
                    "User with ID {$userId} not found or version mismatch",
                    404
                );
            }

            if (isset($data['name'])) {
                $user->name = $data['name'];
            }

            if (isset($data['email'])) {
                $user->email = mb_strtolower(trim($data['email']));
            }

            $user->version = $expectedVersion + 1;

            if (!$user->save(false)) {
                throw new RuntimeException('Failed to save user: ' . json_encode($user->getErrors()));
            }

            $transaction->commit();

            return $user;
        } catch (Exception $e) {
            $transaction->rollBack();
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
     * @return UserYii
     */
    public function updateUserWithRetry(int $userId, array $data, int $maxRetries = 3): UserYii
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $user = UserYii::findOne($userId);

                if ($user === null) {
                    throw new RuntimeException("User with ID {$userId} not found", 404);
                }

                $version = $user->version;

                if (isset($data['name'])) {
                    $user->name = $data['name'];
                }

                if (isset($data['email'])) {
                    $user->email = mb_strtolower(trim($data['email']));
                }

                $user->version = $version + 1;

                if ($user->save(false)) {
                    return $user;
                }

                throw new RuntimeException('Failed to save user');
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

    /**
     * Batch update with version checking.
     *
     * @param array<array{id: int, name?: string, email?: string, version: int}> $updates
     * @return array<UserYii>
     */
    public function batchUpdate(array $updates): array
    {
        $results = [];
        $errors = [];

        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            foreach ($updates as $index => $update) {
                if (!isset($update['id'], $update['version'])) {
                    $errors[] = "Missing id or version at index {$index}";
                    continue;
                }

                $user = UserYii::find()
                    ->where(['id' => $update['id'], 'version' => $update['version']])
                    ->one();

                if ($user === null) {
                    $errors[] = "Version mismatch or user not found at index {$index}";
                    continue;
                }

                if (isset($update['name'])) {
                    $user->name = $update['name'];
                }

                if (isset($update['email'])) {
                    $user->email = mb_strtolower(trim($update['email']));
                }

                $user->version = $update['version'] + 1;

                if (!$user->save(false)) {
                    $errors[] = "Failed to save at index {$index}";
                    continue;
                }

                $results[] = $user;
            }

            if (!empty($errors) && empty($results)) {
                $transaction->rollBack();
                throw new RuntimeException('All updates failed: ' . implode('; ', $errors));
            }

            $transaction->commit();

            return $results;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
