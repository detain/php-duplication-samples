<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Delete user with cascade" operation.
 */
final class YiiUserRepository
{
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
        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            $user = UserYii::findOne($userId);

            if ($user === null) {
                throw new RuntimeException("User with ID {$userId} not found");
            }

            if ($cascade) {
                Post::deleteAll(['author_id' => $userId]);
                \Yii::$app->authManager->revokeAll($userId);
            }

            $result = $user->delete();

            $transaction->commit();

            return $result > 0;
        } catch (Exception $e) {
            $transaction->rollBack();
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
     * @return UserYii
     */
    public function softDeleteUser(int $userId): UserYii
    {
        $user = UserYii::findOne($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $user->deletedAt = time();

        try {
            $user->save(false);

            return $user;
        } catch (Exception $e) {
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

        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            if ($cascade) {
                Post::deleteAll(['author_id' => $userIds]);

                foreach ($userIds as $userId) {
                    \Yii::$app->authManager->revokeAll($userId);
                }
            }

            $count = UserYii::deleteAll(['id' => $userIds]);

            $transaction->commit();

            return $count;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new RuntimeException(
                'Failed to delete users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
