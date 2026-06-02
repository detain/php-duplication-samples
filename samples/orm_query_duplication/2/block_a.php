<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\SequenceGenerator;
use RuntimeException;

/**
 * User service using Doctrine ORM.
 * Demonstrates "Create new user with auto-generated ID" operation.
 */
final class DoctrineUserService
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Create a new user and return the generated ID.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return User The created user with ID assigned
     * @throws RuntimeException If creation fails
     */
    public function createUser(array $data): User
    {
        $this->validateUserData($data);

        $normalizedEmail = mb_strtolower(trim($data['email']));

        $this->ensureEmailUniqueness($normalizedEmail);

        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($normalizedEmail);
        $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        try {
            $this->em->persist($user);
            $this->em->flush();

            return $user;
        } catch (\Doctrine\ORM\ORMException $e) {
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
     * @return array<User> The created users
     */
    public function createUsersBatch(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $createdUsers = [];
        $batchSize = 50;

        $this->em->beginTransaction();

        try {
            foreach ($users as $index => $data) {
                $this->validateUserData($data);
                $normalizedEmail = mb_strtolower(trim($data['email']));
                $this->ensureEmailUniqueness($normalizedEmail);

                $user = new User();
                $user->setName($data['name']);
                $user->setEmail($normalizedEmail);
                $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());

                $this->em->persist($user);
                $createdUsers[] = $user;

                if (($index + 1) % $batchSize === 0) {
                    $this->em->flush();
                    $this->em->clear(User::class);
                }
            }

            $this->em->flush();
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
     * Create user and send welcome email (demonstrates transaction scope).
     *
     * @param array{name: string, email: string, password: string} $data
     * @param callable|null $onSuccess Optional callback after successful creation
     * @return User
     */
    public function createUserWithWelcomeEmail(
        array $data,
        ?callable $onSuccess = null
    ): User {
        $this->em->beginTransaction();

        try {
            $user = $this->createUser($data);

            $this->em->commit();

            if ($onSuccess !== null) {
                $onSuccess($user);
            }

            return $user;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
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
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existing !== null) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
