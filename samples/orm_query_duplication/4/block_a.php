<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\UserRole;
use Doctrine\ORM\EntityManager;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Delete user with cascade" operation.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Delete user with cascade to related entities.
     *
     * @param int $userId
     * @param bool $cascade Whether to cascade delete related entities
     * @return bool True if deleted
     * @throws RuntimeException If deletion fails
     */
    public function deleteUser(int $userId, bool $cascade = true): bool
    {
        $user = $this->em->find(User::class, $userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        try {
            if ($cascade) {
                $this->cascadeDelete($user);
            }

            $this->em->remove($user);
            $this->em->flush();

            return true;
        } catch (\Doctrine\ORM\ORMException $e) {
            throw new RuntimeException(
                'Failed to delete user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Delete user and all related posts.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUserWithPosts(int $userId): bool
    {
        $user = $this->em->find(User::class, $userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $this->em->beginTransaction();

        try {
            $posts = $this->em->getRepository(Post::class)
                ->findBy(['author' => $user]);

            foreach ($posts as $post) {
                $this->em->remove($post);
            }

            $roles = $this->em->getRepository(UserRole::class)
                ->findBy(['user' => $user]);

            foreach ($roles as $role) {
                $this->em->remove($role);
            }

            $this->em->remove($user);
            $this->em->flush();
            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new RuntimeException(
                'Failed to delete user with posts: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Soft delete user (mark as deleted).
     *
     * @param int $userId
     * @return User
     */
    public function softDeleteUser(int $userId): User
    {
        $user = $this->em->find(User::class, $userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $user->setDeletedAt(new \DateTimeImmutable());

        try {
            $this->em->flush();

            return $user;
        } catch (\Doctrine\ORM\ORMException $e) {
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
     * @return int Number of deleted users
     */
    public function deleteUsers(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;

        $this->em->beginTransaction();

        try {
            foreach ($userIds as $userId) {
                $user = $this->em->find(User::class, $userId);

                if ($user !== null) {
                    $this->em->remove($user);
                    $count++;
                }
            }

            $this->em->flush();
            $this->em->commit();

            return $count;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new RuntimeException(
                'Failed to delete users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function cascadeDelete(User $user): void
    {
        $posts = $this->em->getRepository(Post::class)
            ->findBy(['author' => $user]);

        foreach ($posts as $post) {
            $this->em->remove($post);
        }

        $roles = $this->em->getRepository(UserRole::class)
            ->findBy(['user' => $user]);

        foreach ($roles as $role) {
            $this->em->remove($role);
        }
    }
}
