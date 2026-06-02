<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\EntityManager;
use Cycle\ORM\Select\Repository;
use App\Entity\User;
use App\Entity\Post;
use RuntimeException;

/**
 * User repository using Cycle ORM.
 * Demonstrates "Delete user with cascade" operation.
 */
final class CycleUserRepository extends Repository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Delete user with cascade.
     *
     * @param int $userId
     * @param bool $cascade
     * @return bool
     * @throws RuntimeException
     */
    public function deleteUser(int $userId, bool $cascade = true): bool
    {
        $user = $this->findByPK($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $this->em->begin();

        try {
            if ($cascade) {
                $posts = $this->select()
                    ->with('posts')
                    ->where('id', $userId)
                    ->fetchOne();

                if ($posts !== null) {
                    foreach ($posts->posts as $post) {
                        $this->em->delete($post);
                    }
                }

                $roles = $this->select()
                    ->with('roles')
                    ->where('id', $userId)
                    ->fetchOne();

                if ($roles !== null) {
                    foreach ($roles->roles as $role) {
                        $this->em->delete($role);
                    }
                }
            }

            $this->em->delete($user);
            $this->em->run();
            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new RuntimeException(
                'Failed to delete user: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Soft delete user.
     *
     * @param int $userId
     * @return User
     */
    public function softDeleteUser(int $userId): User
    {
        $user = $this->findByPK($userId);

        if ($user === null) {
            throw new RuntimeException("User with ID {$userId} not found");
        }

        $user->deletedAt = new \DateTimeImmutable();

        try {
            $this->em->persist($user);
            $this->em->run();

            return $user;
        } catch (\Exception $e) {
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
     * @return int
     */
    public function deleteUsers(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $count = 0;

        $this->em->begin();

        try {
            foreach ($userIds as $userId) {
                $user = $this->findByPK($userId);

                if ($user !== null) {
                    $this->em->delete($user);
                    $count++;
                }
            }

            $this->em->run();
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
}
