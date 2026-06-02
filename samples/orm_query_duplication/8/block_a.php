<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\Role;
use Doctrine\ORM\EntityManager;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Join/Relations" operation - fetching user with posts and roles.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get user with posts and roles eagerly loaded.
     *
     * @param int $userId
     * @return User|null
     */
    public function getUserWithRelations(int $userId): ?User
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('u', 'p', 'r')
           ->from(User::class, 'u')
           ->leftJoin('u.posts', 'p')
           ->leftJoin('u.roles', 'r')
           ->where('u.id = :userId')
           ->setParameter('userId', $userId);

        try {
            $result = $qb->getQuery()->getOneOrNullResult();

            return $result;
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get user with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users with their post counts.
     *
     * @param int $limit
     * @return array<array{user: User, postCount: int}>
     */
    public function getUsersWithPostCounts(int $limit = 100): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('u', 'COUNT(p.id) as postCount')
           ->from(User::class, 'u')
           ->leftJoin('u.posts', 'p')
           ->groupBy('u.id')
           ->orderBy('postCount', 'DESC')
           ->setMaxResults($limit);

        try {
            $results = $qb->getQuery()->getResult();

            return array_map(fn($row) => [
                'user' => $row[0],
                'postCount' => (int) $row['postCount'],
            ], $results);
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get users with post counts: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users with their latest post.
     *
     * @param int $limit
     * @return array<array{user: User, latestPost: Post|null}>
     */
    public function getUsersWithLatestPost(int $limit = 100): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('u', 'p')
           ->from(User::class, 'u')
           ->leftJoin('u.posts', 'p', 'WITH', 'p.id = (SELECT MAX(p2.id) FROM App\Entity\Post p2 WHERE p2.author = u)')
           ->setMaxResults($limit);

        try {
            $results = $qb->getQuery()->getResult();

            return array_map(fn($row) => [
                'user' => $row[0],
                'latestPost' => $row['p'] ?? null,
            ], $results);
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get users with latest post: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users grouped by role.
     *
     * @return array<array{role: Role, users: array<User>}>
     */
    public function getUsersGroupedByRole(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('r', 'u')
           ->from(Role::class, 'r')
           ->leftJoin('r.users', 'u')
           ->orderBy('r.name', 'ASC');

        try {
            $results = $qb->getQuery()->getResult();

            $grouped = [];
            foreach ($results as $row) {
                $role = $row[0];
                $users = $row['users'] ?? [];
                $grouped[] = [
                    'role' => $role,
                    'users' => $users,
                ];
            }

            return $grouped;
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get users grouped by role: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get users who have posts in specific categories.
     *
     * @param array<int> $categoryIds
     * @return array<User>
     */
    public function getUsersWithPostsInCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('DISTINCT u')
           ->from(User::class, 'u')
           ->join('u.posts', 'p')
           ->join('p.category', 'c')
           ->where('c.id IN (:categoryIds)')
           ->setParameter('categoryIds', $categoryIds)
           ->orderBy('u.name', 'ASC');

        try {
            return $qb->getQuery()->getResult();
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get users with posts in categories: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
