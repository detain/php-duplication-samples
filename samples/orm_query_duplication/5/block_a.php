<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;

/**
 * User repository using Doctrine ORM.
 * Demonstrates "Search users with WHERE clause" operation.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Search users with multiple WHERE conditions.
     *
     * @param array{name?: string, email?: string, status?: string, createdAfter?: \DateTimeInterface} $criteria
     * @param int $limit
     * @param int $offset
     * @return array<User>
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u');

        $paramIndex = 0;

        if (!empty($criteria['name'])) {
            $qb->andWhere('u.name LIKE :name' . $paramIndex)
               ->setParameter($paramIndex++, '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['email'])) {
            $qb->andWhere('u.email LIKE :email' . $paramIndex)
               ->setParameter($paramIndex++, '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('u.status = :status' . $paramIndex)
               ->setParameter($paramIndex++, $criteria['status']);
        }

        if (!empty($criteria['createdAfter'])) {
            $qb->andWhere('u.createdAt >= :createdAfter' . $paramIndex)
               ->setParameter($paramIndex++, $criteria['createdAfter']);
        }

        $qb->orderBy('u.createdAt', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        try {
            return $qb->getQuery()->getResult();
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Search users with OR conditions.
     *
     * @param array<string, mixed> $criteria
     * @return array<User>
     */
    public function searchUsersOr(array $criteria): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u');

        $orX = $qb->expr()->orX();

        foreach ($criteria as $key => $value) {
            if ($key === 'name' || $key === 'email') {
                $paramName = $key . '_' . uniqid();
                $orX->add($qb->expr()->like('u.' . $key, ':' . $paramName));
                $qb->setParameter($paramName, '%' . $value . '%');
            }
        }

        if ($orX->count() > 0) {
            $qb->where($orX);
        }

        $qb->orderBy('u.createdAt', 'DESC');

        try {
            return $qb->getQuery()->getResult();
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users by status with count.
     *
     * @param string $status
     * @return array{users: array<User>, count: int}
     */
    public function findByStatus(string $status): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('COUNT(u.id)')
           ->from(User::class, 'u')
           ->where('u.status = :status')
           ->setParameter('status', $status);

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        $qb->select('u')
           ->orderBy('u.createdAt', 'DESC');

        $users = $qb->getQuery()->getResult();

        return ['users' => $users, 'count' => $count];
    }

    /**
     * Search users with advanced filtering.
     *
     * @param array{
     *     name?: string,
     *     email?: string,
     *     status?: string[],
     *     roleIds?: int[],
     *     createdBetween?: array{start: \DateTimeInterface, end: \DateTimeInterface},
     *     hasPosts?: bool
     * } $criteria
     * @return array<User>
     */
    public function advancedSearch(array $criteria): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('DISTINCT u')
           ->from(User::class, 'u');

        $paramIndex = 0;

        if (!empty($criteria['name'])) {
            $qb->andWhere('u.name LIKE :name' . $paramIndex)
               ->setParameter($paramIndex++, '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['email'])) {
            $qb->andWhere('u.email LIKE :email' . $paramIndex)
               ->setParameter($paramIndex++, '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $qb->andWhere('u.status IN (:status' . $paramIndex . ')')
               ->setParameter($paramIndex++, $criteria['status']);
        }

        if (!empty($criteria['createdBetween'])) {
            $qb->andWhere('u.createdAt BETWEEN :startDate' . $paramIndex . ' AND :endDate' . $paramIndex)
               ->setParameter($paramIndex++, $criteria['createdBetween']['start'])
               ->setParameter($paramIndex++, $criteria['createdBetween']['end']);
        }

        $qb->orderBy('u.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
