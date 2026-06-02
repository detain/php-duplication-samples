<?php
declare(strict_types=1);

namespace App\Database\Cycle;

use Cycle\ORM\Select\Repository;
use Cycle\ORM\Select\Scope;
use App\Entity\User;
use RuntimeException;

/**
 * Repository for User entity using Cycle ORM.
 * Demonstrates "Find user by email" operation in Cycle repository style.
 */
final class CycleUserRepository extends Repository
{
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
            return $this->select()
                ->where('email', $normalizedEmail)
                ->fetchOne();
        } catch (\Cycle\Database\Exception\StatementException $e) {
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
     * @param string $email The email to search for
     * @param array<string> $relations Relations to eager load
     * @return User|null
     */
    public function findByEmailWith(string $email, array $relations = []): ?User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $select = $this->select()->with($relations)->where('email', $normalizedEmail);

            return $select->fetchOne();
        } catch (\Cycle\Database\Exception\StatementException $e) {
            throw new RuntimeException(
                'Failed to find user by email with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users by email domain.
     *
     * @param string $domain The email domain to search for
     * @return array<User>
     */
    public function findByEmailDomain(string $domain): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        try {
            return $this->select()
                ->where('email', 'LIKE', "%@{$normalizedDomain}")
                ->fetchAll();
        } catch (\Cycle\Database\Exception\StatementException $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if a user exists with the given email.
     *
     * @param string $email The email to check
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return $this->select()
                ->where('email', $normalizedEmail)
                ->count() > 0;
        } catch (\Cycle\Database\Exception\StatementException $e) {
            throw new RuntimeException(
                'Failed to check user existence: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find user by email or fail with exception.
     *
     * @param string $email The email to search for
     * @return User
     * @throws RuntimeException
     */
    public function findByEmailOrFail(string $email): User
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            throw new RuntimeException("User with email '{$email}' not found");
        }

        return $user;
    }

    /**
     * Get the user repository with pagination support.
     *
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array{data: array<User>, total: int, page: int, pages: int}
     */
    public function getPaginatedByDomain(string $domain, int $page = 1, int $limit = 20): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        $select = $this->select()
            ->where('email', 'LIKE', "%@{$normalizedDomain}");

        $total = $select->count();

        $pages = (int) ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        $data = $this->select()
            ->where('email', 'LIKE', "%@{$normalizedDomain}")
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}
