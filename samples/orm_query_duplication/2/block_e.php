<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\db\ActiveRecord;
use yii\db\Exception;
use RuntimeException;

/**
 * User service using Yii2 ActiveRecord.
 * Demonstrates "Create new user with auto-generated ID" operation.
 */
final class YiiUserService
{
    /**
     * Create a new user and return the model with generated ID.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserYii
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): UserYii
    {
        $this->validateUserData($data);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        $this->ensureEmailUniqueness($normalizedEmail);

        $user = new UserYii();
        $user->name = $data['name'];
        $user->email = $normalizedEmail;
        $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
        $user->generateAuthKey();
        $user->generatePasswordResetToken();

        try {
            if (!$user->save(false)) {
                throw new RuntimeException(
                    'Failed to save user: ' . json_encode($user->getErrors())
                );
            }

            return $user;
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to create user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create multiple users in a batch.
     *
     * @param array<array{name: string, email: string, password: string}> $users
     * @return array<UserYii>
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $batchSize = 50;

        $db = UserYii::getDb();
        $transaction = $db->beginTransaction();

        try {
            foreach ($users as $index => $data) {
                $this->validateUserData($data);
                $normalizedEmail = mb_strtolower(trim($data['email']));
                $this->ensureEmailUniqueness($normalizedEmail);

                $user = new UserYii();
                $user->name = $data['name'];
                $user->email = $normalizedEmail;
                $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                $user->generateAuthKey();

                if (!$user->save(false)) {
                    throw new RuntimeException(
                        'Failed to save user at index ' . $index . ': ' . json_encode($user->getErrors())
                    );
                }

                $createdUsers[] = $user;

                if (($index + 1) % $batchSize === 0) {
                    $transaction->commit();
                    $transaction = $db->beginTransaction();
                }
            }

            $transaction->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new RuntimeException(
                'Failed to create users batch: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create user with role assignments.
     *
     * @param array{name: string, email: string, password: string} $data
     * @param array<int> $roleIds
     * @return UserYii
     */
    public function createUserWithRoles(array $data, array $roleIds = []): UserYii
    {
        $user = $this->createUser($data);

        if (!empty($roleIds)) {
            $auth = \Yii::$app->authManager;
            foreach ($roleIds as $roleId) {
                $auth->assign($auth->getRole($roleId), $user->id);
            }
        }

        return $user;
    }

    /**
     * Find or create user by email.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return UserYii
     */
    public function findOrCreateUser(array $data): UserYii
    {
        $this->validateUserData($data);
        $normalizedEmail = mb_strtolower(trim($data['email']));

        $existing = UserYii::findOne(['email' => $normalizedEmail]);

        if ($existing !== null) {
            return $existing;
        }

        return $this->createUser($data);
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
        $existing = UserYii::findOne(['email' => $email]);

        if ($existing !== null) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
