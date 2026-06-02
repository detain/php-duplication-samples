<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\db\Exception;
use RuntimeException;

/**
 * User repository using Yii2 ActiveRecord.
 * Demonstrates "Batch insert users" operation for bulk data imports.
 */
final class YiiUserRepository
{
    /**
     * Batch insert users efficiently.
     *
     * @param array<array{name: string, email: string, password: string, status?: string}> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array<UserYii>
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

        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);
                $insertBatch = [];

                foreach ($batch as $data) {
                    $this->validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));

                    if (UserYii::find()->where(['email' => $normalizedEmail])->exists()) {
                        throw new RuntimeException("User with email '{$normalizedEmail}' already exists");
                    }

                    $insertBatch[] = [
                        'name' => $data['name'],
                        'email' => $normalizedEmail,
                        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                        'status' => $data['status'] ?? 'active',
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }

                $db->createCommand()->batchInsert(UserYii::tableName(), [
                    'name', 'email', 'password', 'status', 'created_at', 'updated_at',
                ], $insertBatch)->execute();

                $emails = array_column($insertBatch, 'email');
                $insertedUsers = UserYii::find()->where(['email' => $emails])->all();
                $createdUsers = array_merge($createdUsers, $insertedUsers);

                $processed += count($batch);

                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }
            }

            $transaction->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $transaction->rollBack();
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
     * @return array<UserYii>
     */
    public function bulkUpsertUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $results = [];

        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                if (isset($data['id'])) {
                    $user = UserYii::findOne($data['id']);

                    if ($user !== null) {
                        $user->name = $data['name'];
                        $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                        $user->save(false);
                        $results[] = $user;
                        continue;
                    }
                }

                $user = UserYii::find()->where(['email' => $normalizedEmail])->one();

                if ($user !== null) {
                    $user->name = $data['name'];
                    $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $user->save(false);
                    $results[] = $user;
                } else {
                    $user = new UserYii();
                    $user->name = $data['name'];
                    $user->email = $normalizedEmail;
                    $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $user->save(false);
                    $results[] = $user;
                }
            }

            $transaction->commit();

            return $results;
        } catch (\Exception $e) {
            $transaction->rollBack();
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
}
