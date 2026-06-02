<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\Transaction\EntityManager;
use App\Entity\User;
use RuntimeException;

/**
 * User service using Cycle ORM.
 * Demonstrates "Create new user with auto-generated ID" operation.
 */
final class CycleUserService
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Create a new user and return the entity with ID assigned.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return User
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): User
    {
        $this->validateUserData($data);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        $this->ensureEmailUniqueness($normalizedEmail);

        $user = new User();
        $user->name = $data['name'];
        $user->email = $normalizedEmail;
        $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
        $user->createdAt = new \DateTimeImmutable();
        $user->updatedAt = new \DateTimeImmutable();

        try {
            $this->em->persist($user);
            $this->em->run();

            return $user;
        } catch (\Cycle\Database\Exception\StatementException $e) {
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
     * @return array<User>
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $batchSize = 50;

        $this->em->begin();

        try {
            foreach ($users as $index => $data) {
                $this->validateUserData($data);
                $normalizedEmail = mb_strtolower(trim($data['email']));
                $this->ensureEmailUniqueness($normalizedEmail);

                $user = new User();
                $user->name = $data['name'];
                $user->email = $normalizedEmail;
                $user->password = password_hash($data['password'], PASSWORD_ARGON2ID);
                $user->createdAt = new \DateTimeImmutable();
                $user->updatedAt = new \DateTimeImmutable();

                $this->em->persist($user);
                $createdUsers[] = $user;

                if (($index + 1) % $batchSize === 0) {
                    $this->em->run();
                }
            }

            $this->em->run();
            $this->em->commit();

            return $createdUsers;
        } catch (\Exception $e) {
            $this->em->rollback();
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
     * @return User
     */
    public function createUserWithRoles(array $data, array $roleIds = []): User
    {
        $user = $this->createUser($data);

        if (!empty($roleIds)) {
            foreach ($roleIds as $roleId) {
                $user->addRole($roleId);
            }
            $this->em->persist($user);
            $this->em->run();
        }

        return $user;
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
