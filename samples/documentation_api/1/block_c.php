<?php

declare(strict_types=1);

namespace Api\Customers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Customer repository for database operations
 *
 * Provides methods for querying and managing customer records
 * with support for pagination, filtering, and eager/lazy loading.
 */
class CustomerRepository
{
    public function __construct(
        private EntityManager $entityManager
    ) {
    }

    /**
     * Find customer by email address
     *
     * @param string $email The email to search for (case-insensitive)
     * @return Customer|null The customer if found, null otherwise
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->entityManager
            ->getRepository(Customer::class)
            ->findOneBy(['email' => strtolower($email)]);
    }

    /**
     * Find customer by ID
     *
     * @param int $id The customer ID
     * @return Customer|null The customer if found, null otherwise
     */
    public function findById(int $id): ?Customer
    {
        return $this->entityManager->find(Customer::class, $id);
    }

    /**
     * Find customers with pagination
     *
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param array $filters Optional filters (search, status, date_range)
     * @param string $sortBy Field to sort by
     * @param string $sortDirection Sort direction (ASC or DESC)
     * @return Paginator Paginated customer results
     */
    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        array $filters = [],
        string $sortBy = 'createdAt',
        string $sortDirection = 'DESC'
    ): Paginator {
        $qb = $this->entityManager
            ->getRepository(Customer::class)
            ->createQueryBuilder('c')
            ->orderBy('c.' . $sortBy, $sortDirection)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $qb->andWhere('c.email LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search')
                ->setParameter('search', $searchTerm);
        }

        if (!empty($filters['marketing_consent'])) {
            $qb->andWhere('c.marketingConsent = :consent')
                ->setParameter('consent', (bool) $filters['marketing_consent']);
        }

        if (!empty($filters['referral_code'])) {
            $qb->andWhere('c.referralCode = :referral')
                ->setParameter('referral', $filters['referral_code']);
        }

        if (!empty($filters['created_after'])) {
            $qb->andWhere('c.createdAt >= :after')
                ->setParameter('after', new \DateTimeImmutable($filters['created_after']));
        }

        if (!empty($filters['created_before'])) {
            $qb->andWhere('c.createdAt <= :before')
                ->setParameter('before', new \DateTimeImmutable($filters['created_before']));
        }

        return new Paginator($qb->getQuery(), fetchJoin: false);
    }

    /**
     * Count customers matching filters
     *
     * @param array $filters Optional filters
     * @return int Total count
     */
    public function count(array $filters = []): int
    {
        $qb = $this->entityManager
            ->getRepository(Customer::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)');

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $qb->andWhere('c.email LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search')
                ->setParameter('search', $searchTerm);
        }

        if (!empty($filters['marketing_consent'])) {
            $qb->andWhere('c.marketingConsent = :consent')
                ->setParameter('consent', (bool) $filters['marketing_consent']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find customers by referral code
     *
     * @param string $referralCode The referral code to search
     * @return array Array of customers referred by this code
     */
    public function findByReferralCode(string $referralCode): array
    {
        return $this->entityManager
            ->getRepository(Customer::class)
            ->findBy(['referralCode' => $referralCode]);
    }

    /**
     * Get customer statistics
     *
     * @return array Statistics including total customers, new today, etc.
     */
    public function getStatistics(): array
    {
        $conn = $this->entityManager->getConnection();

        $totalResult = $conn->executeQuery('SELECT COUNT(*) FROM customers')->fetchOne();

        $todayResult = $conn->executeQuery(
            'SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURRENT_DATE'
        )->fetchOne();

        $withMarketingResult = $conn->executeQuery(
            'SELECT COUNT(*) FROM customers WHERE marketing_consent = 1'
        )->fetchOne();

        return [
            'total' => (int) $totalResult,
            'new_today' => (int) $todayResult,
            'with_marketing_consent' => (int) $withMarketingResult,
        ];
    }

    /**
     * Check if email is already registered
     *
     * @param string $email The email to check
     * @param int|null $excludeId Customer ID to exclude from check
     * @return bool True if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $qb = $this->entityManager
            ->getRepository(Customer::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.email = :email')
            ->setParameter('email', strtolower($email));

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Get recently created customers
     *
     * @param int $limit Number of customers to return
     * @return array Array of recently created customers
     */
    public function findRecentlyCreated(int $limit = 10): array
    {
        return $this->entityManager
            ->getRepository(Customer::class)
            ->findBy(
                [],
                ['createdAt' => 'DESC'],
                $limit
            );
    }

    /**
     * Search customers by name or email
     *
     * @param string $query Search query
     * @param int $limit Maximum results
     * @return array Matching customers
     */
    public function search(string $query, int $limit = 20): array
    {
        $term = '%' . $query . '%';

        return $this->entityManager
            ->getRepository(Customer::class)
            ->createQueryBuilder('c')
            ->where('c.email LIKE :term OR c.firstName LIKE :term OR c.lastName LIKE :term')
            ->setParameter('term', $term)
            ->setMaxResults($limit)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Flush pending changes to database
     */
    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * Persist a customer entity
     *
     * @param Customer $customer The customer to persist
     * @param bool $flush Whether to flush immediately
     */
    public function persist(Customer $customer, bool $flush = true): void
    {
        $this->entityManager->persist($customer);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Remove a customer
     *
     * @param Customer $customer The customer to remove
     * @param bool $flush Whether to flush immediately
     */
    public function remove(Customer $customer, bool $flush = true): void
    {
        $this->entityManager->remove($customer);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
