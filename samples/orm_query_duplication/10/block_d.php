<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\EntityManager;
use App\Entity\User;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Batch insert users" operation for bulk data imports.
 */
final class CycleUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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

        $this->em->begin();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);

                foreach ($batch as $data) {
                    $this->validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));
                    $this->ensureEmailUniqueness($normalizedEmail);

                    $user = new User();
                    $user->name = $data['name'];
                    $user->email = $normalizedEmail;
                    $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $user->status = $data['status'] ?? 'active';
                    $user->createdAt = new \DateTimeImmutable();

                    $this->em->persist($user);
                    $createdUsers[] = $user;
                }

                $this->em->run();

                $processed += count($batch);

                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }
            }

            $this->em->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $this->em->rollback();
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

        $this->em->begin();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                $repository = $this->em->getRepository(User::class);

                if (isset($data['id'])) {
                    $user = $repository->findByPK($data['id']);

                    if ($user !== null) {
                        $user->name = $data['name'];
                        $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                        $user->updatedAt = new \DateTimeImmutable();
                        $this->em->persist($user);
                        $results[] = $user;
                        continue;
                    }
                }

                $existingUser = $repository->findOne(where: ['email' => $normalizedEmail]);

                if ($existingUser !== null) {
                    $existingUser->name = $data['name'];
                    $existingUser->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $existingUser->updatedAt = new \DateTimeImmutable();
                    $this->em->persist($existingUser);
                    $results[] = $existingUser;
                } else {
                    $user = new User();
                    $user->name = $data['name'];
                    $user->email = $normalizedEmail;
                    $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                    $user->createdAt = new \DateTimeImmutable();
                    $this->em->persist($user);
                    $results[] = $user;
                }
            }

            $this->em->run();
            $this->em->commit();

            return $results;
        } catch (\Exception $e) {
            $this->em->rollback();
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

    private function ensureEmailUniqueness(string $email): void
    {
        $repository = $this->em->getRepository(User::class);
        $existing = $repository->findOne(where: ['email' => $email]);

        if ($existing !== null) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
