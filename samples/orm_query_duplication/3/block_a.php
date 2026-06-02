<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Update user with optimistic locking.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion Expected version for optimistic lock
     * @return User The updated user
     * @throws RuntimeException If update fails or version mismatch
     */
    public function updateUser(int $userId, array $data, int $expectedVersion): User
    {
        $user = $this->em->find(User::class, $userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        try {
            $this->em->lock($user, \Doctrine\DBAL\LockMode::OPTIMISTIC, $expectedVersion);
        } catch (OptimisticLockException $e) {
            throw new RuntimeException(
                'Version mismatch: user was modified by another process',
                409,
                $e
            );
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $normalizedEmail = mb_strtolower(trim($data['email']));
            $this->ensureEmailUniqueness($normalizedEmail, $userId);
            $user->setEmail($normalizedEmail);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());

        try {
            $this->em->flush();

            return $user;
        } catch (OptimisticLockException $e) {
            throw new RuntimeException(
                'Concurrent modification detected: please retry',
                409,
                $e
            );
        } catch (\Doctrine\ORM\ORMException $e) {
            throw new RuntimeException(
                'Failed to update user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Update user with retry on version conflict.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $maxRetries
     * @return User
     */
    public function updateUserWithRetry(int $userId, array $data, int $maxRetries = 3): User
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $user = $this->em->find(User::class, $userId);

                if ($user === null) {
                    throw new RuntimeException("User with ID {$userId} not found");
                }

                $version = $user->getVersion();

                if (isset($data['name'])) {
                    $user->setName($data['name']);
                }

                if (isset($data['email'])) {
                    $user->setEmail(mb_strtolower(trim($data['email'])));
                }

                $user->setUpdatedAt(new \DateTimeImmutable());

                $this->em->flush();

                return $user;
            } catch (OptimisticLockException $e) {
                $attempts++;
                $this->em->clear(User::class);

                if ($attempts >= $maxRetries) {
                    throw new RuntimeException(
                        'Update failed after ' . $maxRetries . ' attempts due to concurrent modifications',
                        409,
                        $e
                    );
                }

                usleep(100000 * $attempts);
            }
        }

        throw new RuntimeException('Update failed: max retries exceeded');
    }

    private function ensureEmailUniqueness(string $email, int $excludeUserId): void
    {
        $existing = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existing !== null && $existing->getId() !== $excludeUserId) {
            throw new RuntimeException("User with email '{$email}' already exists");
        }
    }
}
