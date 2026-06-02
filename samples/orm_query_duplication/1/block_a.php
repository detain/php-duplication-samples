<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;

/**
 * Repository for User entity using Doctrine ORM.
 * Demonstrates "Find user by email" operation in Doctrine DQL style.
 */
final class DoctrineUserRepository
{
    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     * @return User|null The found user or null if not exists
     * @throws RuntimeException If query execution fails
     */
    public function findByEmail(string $email): ?User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $dql = 'SELECT u FROM App\Entity\User u WHERE LOWER(u.email) = :email';
            $query = $this->em->createQuery($dql);
            $query->setParameter('email', $normalizedEmail);
            $query->setMaxResults(1);

            $result = $query->getResult();

            if (count($result) === 0) {
                return null;
            }

            return $result[0];
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email with eager loading of relations.
     *
     * @param string $email The email address to search for
     * @param array<string> $relations Relations to eager load
     * @return User|null The found user or null
     */
    public function findByEmailWithRelations(string $email, array $relations = []): ?User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        /** @var QueryBuilder $qb */
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u');

        foreach ($relations as $index => $relation) {
            $qb->leftJoin("u.{$relation}", "r{$index}");
        }

        $qb->where('LOWER(u.email) = :email')
           ->setParameter('email', $normalizedEmail)
           ->setMaxResults(1);

        try {
            $result = $qb->getQuery()->getResult();

            if (count($result) === 0) {
                return null;
            }

            return $result[0];
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to find user by email with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if a user exists with the given email.
     *
     * @param string $email The email to check
     * @return bool True if user exists
     */
    public function existsByEmail(string $email): bool
    {
        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $qb = $this->em->createQueryBuilder();
            $qb->select('COUNT(u.id)')
               ->from(User::class, 'u')
               ->where('LOWER(u.email) = :email')
               ->setParameter('email', $normalizedEmail);

            $count = (int) $qb->getQuery()->getSingleScalarResult();

            return $count > 0;
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to check user existence: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users by email domain (example of a more complex query).
     *
     * @param string $domain The email domain to search for
     * @return array<User> Array of matching users
     */
    public function findByEmailDomain(string $domain): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        $pattern = "%@{$normalizedDomain}";

        try {
            $dql = 'SELECT u FROM App\Entity\User u WHERE u.email LIKE :pattern';
            $query = $this->em->createQuery($dql);
            $query->setParameter('pattern', $pattern);

            return $query->getResult();
        } catch (\Doctrine\ORM\QueryException $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
