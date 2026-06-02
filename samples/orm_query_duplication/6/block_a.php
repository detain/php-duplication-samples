<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use App\Entity\Post;
use Doctrine\ORM\EntityManager;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Aggregation (count, sum, avg)" operation.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('u.status, COUNT(u.id) as cnt')
           ->from(User::class, 'u')
           ->groupBy('u.status');

        try {
            $results = $qb->getQuery()->getResult();

            $counts = [];
            foreach ($results as $row) {
                $counts[$row['status']] = (int) $row['cnt'];
            }

            return $counts;
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to count users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Calculate average posts per user.
     *
     * @return float
     */
    public function averagePostsPerUser(): float
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('COUNT(p.id) as postCount, COUNT(DISTINCT p.author) as userCount')
           ->from(Post::class, 'p');

        try {
            $result = $qb->getQuery()->getSingleResult();

            $postCount = (int) $result['postCount'];
            $userCount = (int) $result['userCount'];

            if ($userCount === 0) {
                return 0.0;
            }

            return round($postCount / $userCount, 2);
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to calculate average: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Sum of total views across all posts.
     *
     * @return int
     */
    public function sumPostViews(): int
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('SUM(p.viewCount) as totalViews')
           ->from(Post::class, 'p');

        try {
            $result = $qb->getQuery()->getSingleScalarResult();

            return (int) ($result ?? 0);
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to sum views: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get user statistics.
     *
     * @return array{
     *     totalUsers: int,
     *     activeUsers: int,
     *     inactiveUsers: int,
     *     usersWithPosts: int,
     *     usersWithoutPosts: int,
     *     averagePostsPerUser: float
     * }
     */
    public function getUserStatistics(): array
    {
        $totalUsers = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();

        $activeUsers = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        $usersWithPosts = (int) $this->em->createQueryBuilder()
            ->select('COUNT(DISTINCT u.id)')
            ->from(User::class, 'u')
            ->join('u.posts', 'p')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $totalUsers - $activeUsers,
            'usersWithPosts' => $usersWithPosts,
            'usersWithoutPosts' => $totalUsers - $usersWithPosts,
            'averagePostsPerUser' => $this->averagePostsPerUser(),
        ];
    }

    /**
     * Get top users by post count.
     *
     * @param int $limit
     * @return array<array{user: User, postCount: int}>
     */
    public function getTopUsersByPostCount(int $limit = 10): array
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
                'Failed to get top users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
