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
 * Demonstrates "Batch insert users" operation for bulk data imports.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
    }

    /**
     * Batch insert users efficiently.
     *
     * @param array<array{name: string, email: string, password: string, status?: string}> $users
     * @param int $batchSize
     * @param callable|null $progressCallback
     * @return array<User>
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

        R::begin();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);

                foreach ($batch as $data) {
                    $this->validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));

                    if (R::findOne('user', 'email = ?', [$normalizedEmail]) !== null) {
                        throw new RuntimeException("User with email '{$normalizedEmail}' already exists");
                    }

                    $bean = R::dispense('user');
                    $bean->name = $data['name'];
                    $bean->email = $normalizedEmail;
                    $bean->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $bean->status = $data['status'] ?? 'active';
                    $bean->createdAt = (new \DateTime())->format('Y-m-d H:i:s');

                    R::store($bean);
                    $createdUsers[] = $this->mapToEntity($bean);
                }

                $processed += count($batch);

                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }
            }

            R::commit();

            return $createdUsers;
        } catch (\Exception $e) {
            R::rollback();
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
     * @return array<User>
     */
    public function bulkUpsertUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $results = [];

        R::begin();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                if (isset($data['id'])) {
                    $bean = R::load('user', $data['id']);

                    if ($bean->id !== 0) {
                        $bean->name = $data['name'];
                        $bean->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                        $bean->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');
                        R::store($bean);
                        $results[] = $this->mapToEntity($bean);
                        continue;
                    }
                }

                $existingBean = R::findOne('user', 'email = ?', [$normalizedEmail]);

                if ($existingBean !== null) {
                    $existingBean->name = $data['name'];
                    $existingBean->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $existingBean->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');
                    R::store($existingBean);
                    $results[] = $this->mapToEntity($existingBean);
                } else {
                    $bean = R::dispense('user');
                    $bean->name = $data['name'];
                    $bean->email = $normalizedEmail;
                    $bean->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $bean->createdAt = (new \DateTime())->format('Y-m-d H:i:s');
                    R::store($bean);
                    $results[] = $this->mapToEntity($bean);
                }
            }

            R::commit();

            return $results;
        } catch (\Exception $e) {
            R::rollback();
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
