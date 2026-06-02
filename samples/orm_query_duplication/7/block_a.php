<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Pagination" operation.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get paginated users.
     *
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param array<string, mixed> $filters Optional filters
     * @return array{users: array<User>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u');

        if (!empty($filters['status'])) {
            $qb->andWhere('u.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $qb->orderBy('u.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        try {
            $paginator = new Paginator($qb->getQuery(), true);
            $total = count($paginator);
            $totalPages = (int) ceil($total / $perPage);

            $users = [];
            foreach ($paginator as $user) {
                $users[] = $user;
            }

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ];
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get paginated users with cursor-based pagination.
     *
     * @param int $cursor Cursor (last ID seen)
     * @param int $limit
     * @return array{users: array<User>, nextCursor: int|null, hasMore: bool}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): array
    {
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u')
           ->where('u.id > :cursor')
           ->setParameter('cursor', $cursor)
           ->orderBy('u.id', 'ASC')
           ->setMaxResults($limit + 1);

        try {
            $results = $qb->getQuery()->getResult();

            $hasMore = count($results) > $limit;

            if ($hasMore) {
                array_pop($results);
            }

            $nextCursor = !empty($results) ? end($results)->getId() : null;

            return [
                'users' => $results,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
            ];
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get paginated users sorted by activity.
     *
     * @param int $page
     * @param int $perPage
     * @return array{users: array<User>, total: int, page: int, perPage: int, totalPages: int}
     */
    public function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): array
    {
        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u')
           ->leftJoin('u.posts', 'p')
           ->addSelect('COUNT(p.id) as postCount')
           ->groupBy('u.id')
           ->orderBy('postCount', 'DESC')
           ->addOrderBy('u.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        try {
            $paginator = new Paginator($qb->getQuery(), true);
            $total = count($paginator);
            $totalPages = (int) ceil($total / $perPage);

            $users = [];
            foreach ($paginator as $user) {
                $users[] = $user;
            }

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
            ];
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to get paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
