<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\EntityManager;
use App\Entity\User;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Update user with optimistic locking" operation.
 */
final class CycleUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Update user with version checking.
     *
     * @param int $userId
     * @param array{name?: string, email?: string} $data
     * @param int $expectedVersion
     * @return User
     * @throws RuntimeException
     */
    public function updateUser(int $userId, array $data, int $expectedVersion): User
    {
        $repository = $this->em->getRepository(User::class);
        $user = $repository->findByPK($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        if ($user->version !== $expectedVersion) {
            throw new RuntimeException(
                'Version mismatch: user was modified by another process',
                409
            );
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = mb_strtolower(trim($data['email']));
        }

        $user->version = $expectedVersion + 1;
        $user->updatedAt = new \DateTimeImmutable();

        try {
            $this->em->persist($user);
            $this->em->run();

            return $user;
        } catch (\Exception $e) {
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
     * @return User
     */
    public function updateUserWithRetry(int $userId, array $data, int $maxRetries = 3): User
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $this->em->begin();

                $repository = $this->em->getRepository(User::class);
                $user = $repository->findByPK($userId);

                if ($user === null) {
                    throw new RuntimeException("User with ID {$userId} not found");
                }

                if (isset($data['name'])) {
                    $user->name = $data['name'];
                }

                if (isset($data['email'])) {
                    $user->email = mb_strtolower(trim($data['email']));
                }

                $user->version++;
                $user->updatedAt = new \DateTimeImmutable();

                $this->em->persist($user);
                $this->em->run();
                $this->em->commit();

                return $user;
            } catch (\Exception $e) {
                $this->em->rollback();

                if (str_contains($e->getMessage(), 'Version mismatch') || $attempts + 1 >= $maxRetries) {
                    throw new RuntimeException(
                        'Update failed: ' . $e->getMessage(),
                        $e instanceof RuntimeException ? $e->getCode() : 0,
                        $e
                    );
                }

                $attempts++;
                usleep(100000 * $attempts);
            }
        }

        throw new RuntimeException('Update failed: max retries exceeded');
    }
}
