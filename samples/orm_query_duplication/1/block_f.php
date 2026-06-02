<?php
declare(strict_types=1);

namespace App\Database\Cake;

use App\Model\Table\UsersTable;
use App\Model\Entity\User as UserEntity;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use RuntimeException;

/**
 * Repository for User entity using CakePHP ORM.
 * Demonstrates "Find user by email" operation in CakePHP style.
 */
final class CakeUserRepository
{
    private UsersTable $table;

    public function __construct(UsersTable $table)
    {
        $this->table = $table;
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     * @return UserEntity|null The found user or null if not exists
     * @throws RuntimeException If query execution fails
     */
    public function findByEmail(string $email): ?UserEntity
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return $this->table
                ->find()
                ->where(['email' => $normalizedEmail])
                ->first();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email with eager loading of containments.
     *
     * @param string $email The email to search for
     * @param array<string> $contain Associations to contain
     * @return UserEntity|null
     */
    public function findByEmailWith(string $email, array $contain = []): ?UserEntity
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $query = $this->table->find()
                ->where(['email' => $normalizedEmail]);

            if (!empty($contain)) {
                $query->contain($contain);
            }

            return $query->first();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to find user by email with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email or fail with an exception.
     *
     * @param string $email The email to search for
     * @return UserEntity The found user
     * @throws RuntimeException If user not found
     */
    public function findByEmailOrFail(string $email): UserEntity
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $user = $this->table
                ->find()
                ->where(['email' => $normalizedEmail])
                ->firstOrFail();

            return $user;
        } catch (RecordNotFoundException $e) {
            throw new RuntimeException(
                "User with email '{$normalizedEmail}' not found",
                404,
                $e
            );
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
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
        if ($email === '') {
            return false;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return $this->table
                ->exists(['email' => $normalizedEmail]);
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to check user existence: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users by email domain.
     *
     * @param string $domain The email domain to search for
     * @return array<UserEntity>
     */
    public function findByEmailDomain(string $domain): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        try {
            return $this->table
                ->find()
                ->where(['email LIKE' => "%@{$normalizedDomain}"])
                ->all()
                ->toArray();
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get paginated users by domain.
     *
     * @param string $domain The domain to filter by
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @return array{users: array<UserEntity>, total: int, page: int, pages: int}
     */
    public function findPaginatedByDomain(string $domain, int $page = 1, int $limit = 20): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));
        $offset = ($page - 1) * $limit;

        try {
            $query = $this->table->find()
                ->where(['email LIKE' => "%@{$normalizedDomain}"])
                ->orderBy(['id' => 'DESC']);

            $total = $query->count();
            $users = $query->limit($limit)->offset($offset)->all()->toArray();

            $pages = (int) ceil($total / $limit);

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'pages' => $pages,
            ];
        } catch (\Cake\Database\Exception\DatabaseException $e) {
            throw new RuntimeException(
                'Failed to find paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
