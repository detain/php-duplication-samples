<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Batch insert users" operation for bulk data imports.
 */
final class DoctrineUserRepository
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
     * @param int $batchSize Number of users to insert per batch
     * @param callable|null $progressCallback Called after each batch with (processed, total)
     * @return array<User> Created users
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

        $this->em->beginTransaction();

        try {
            for ($i = 0; $i < $total; $i += $batchSize) {
                $batch = array_slice($users, $i, $batchSize);

                foreach ($batch as $data) {
                    $this->validateUserData($data);

                    $normalizedEmail = mb_strtolower(trim($data['email']));
                    $this->ensureEmailUniqueness($normalizedEmail);

                    $user = new User();
                    $user->setName($data['name']);
                    $user->setEmail($normalizedEmail);
                    $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                    $user->setStatus($data['status'] ?? 'active');
                    $user->setCreatedAt(new \DateTimeImmutable());

                    $this->em->persist($user);
                    $createdUsers[] = $user;
                }

                $this->em->flush();
                $this->em->clear(User::class);

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
     * Bulk upsert users (insert or update on duplicate key).
     *
     * @param array<array{id?: int, name: string, email: string, password: string}> $users
     * @return array<User> Updated/created users
     */
    public function bulkUpsertUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $results = [];

        $this->em->beginTransaction();

        try {
            foreach ($users as $data) {
                $normalizedEmail = mb_strtolower(trim($data['email']));

                if (isset($data['id'])) {
                    $user = $this->em->find(User::class, $data['id']);

                    if ($user !== null) {
                        $user->setName($data['name']);
                        $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                        $user->setUpdatedAt(new \DateTimeImmutable());
                        $results[] = $user;
                        continue;
                    }
                }

                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['email' => $normalizedEmail]);

                if ($existingUser !== null) {
                    $existingUser->setName($data['name']);
                    $existingUser->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                    $existingUser->setUpdatedAt(new \DateTimeImmutable());
                    $results[] = $existingUser;
                } else {
                    $user = new User();
                    $user->setName($data['name']);
                    $user->setEmail($normalizedEmail);
                    $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $this->em->persist($user);
                    $results[] = $user;
                }
            }

            $this->em->flush();
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

    /**
     * Import users from CSV data.
     *
     * @param array<array{0: string, 1: string, 2: string}> $csvData CSV rows [name, email, password]
     * @param int $batchSize
     * @return array{imported: int, failed: int, errors: array<string>}
     */
    public function importUsersFromCsv(
        array $csvData,
        int $batchSize = 100
    ): array {
        $imported = 0;
        $failed = 0;
        $errors = [];

        $this->em->beginTransaction();

        try {
            for ($i = 0; $i < count($csvData); $i += $batchSize) {
                $batch = array_slice($csvData, $i, $batchSize);

                foreach ($batch as $rowIndex => $row) {
                    $rowNumber = $i + $rowIndex + 2;

                    if (count($row) < 3) {
                        $errors[] = "Row {$rowNumber}: Invalid format, expected 3 columns";
                        $failed++;
                        continue;
                    }

                    [$name, $email, $password] = $row;

                    try {
                        $data = [
                            'name' => trim($name),
                            'email' => trim($email),
                            'password' => trim($password),
                        ];

                        $this->validateUserData($data);

                        $normalizedEmail = mb_strtolower($data['email']);
                        $this->ensureEmailUniqueness($normalizedEmail);

                        $user = new User();
                        $user->setName($data['name']);
                        $user->setEmail($normalizedEmail);
                        $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                        $user->setCreatedAt(new \DateTimeImmutable());

                        $this->em->persist($user);
                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                        $failed++;
                    }
                }

                $this->em->flush();
                $this->em->clear(User::class);
            }

            $this->em->commit();

            return [
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new RuntimeException(
                'Failed to import users: ' . $e->getMessage(),
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
            throw new RuntimeException('Invalid email format: ' . $data['email']);
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
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existing !== null) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
