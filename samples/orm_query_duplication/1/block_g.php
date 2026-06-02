<?php
declare(strict_types=1);

namespace App\Database\RedBean;

use App\Entity\User;
use RedBeanPHP\OODB;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use RuntimeException;

/**
 * Repository for User entity using RedBeanPHP ORM.
 * Demonstrates "Find user by email" operation in RedBean style.
 */
final class RedBeanUserRepository
{
    private OODB $database;

    public function __construct(?OODB $database = null)
    {
        $this->database = $database ?? R::getFreshDatabaseAdapter()->getDatabase();
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
            $user = R::findOne('user', 'email = ?', [$normalizedEmail]);

            if ($user === null) {
                return null;
            }

            return $this->mapToEntity($user);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email with relations loaded.
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
            $user = R::findOne('user', 'email = ?', [$normalizedEmail]);

            if ($user === null) {
                return null;
            }

            foreach ($relations as $relation) {
                R::load($relation, $user->$relation);
            }

            return $this->mapToEntity($user);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to find user by email with relations: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email or create if not exists.
     *
     * @param string $email The email to search for
     * @param array<string, mixed> $defaults Default values for new user
     * @return User
     */
    public function findOrCreateByEmail(string $email, array $defaults = []): User
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $user = R::findOne('user', 'email = ?', [$normalizedEmail]);

            if ($user !== null) {
                return $this->mapToEntity($user);
            }

            $user = R::dispense('user');
            $user->email = $normalizedEmail;

            foreach ($defaults as $key => $value) {
                $user->$key = $value;
            }

            R::store($user);

            return $this->mapToEntity($user);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to find or create user: ' . $e->getMessage(),
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
            return R::exists('user', 'email = ?', [$normalizedEmail]);
        } catch (RedException $e) {
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
     * @return array<User>
     */
    public function findByEmailDomain(string $domain): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        try {
            $users = R::find('user', 'email LIKE ?', ["%@{$normalizedDomain}"]);

            return array_map([$this, 'mapToEntity'], $users);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Count users by domain.
     *
     * @param string $domain The domain to count by
     * @return int
     */
    public function countByDomain(string $domain): int
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        try {
            return R::count('user', 'email LIKE ?', ["%@{$normalizedDomain}"]);
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to count users by domain: ' . $e->getMessage(),
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
     * @return array{users: array<User>, total: int, page: int, pages: int}
     */
    public function findPaginatedByDomain(string $domain, int $page = 1, int $limit = 20): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));
        $offset = ($page - 1) * $limit;

        try {
            $total = $this->countByDomain($normalizedDomain);
            $pages = (int) ceil($total / $limit);

            $users = R::find('user', 'email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?', [
                "%@{$normalizedDomain}",
                $limit,
                $offset,
            ]);

            return [
                'users' => array_map([$this, 'mapToEntity'], $users),
                'total' => $total,
                'page' => $page,
                'pages' => $pages,
            ];
        } catch (RedException $e) {
            throw new RuntimeException(
                'Failed to find paginated users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Map a RedBean user bean to a User entity.
     *
     * @param \RedBeanPHP\OODBBean $bean
     * @return User
     */
    private function mapToEntity(\RedBeanPHP\OODBBean $bean): User
    {
        $user = new User();
        $user->id = (int) $bean->id;
        $user->email = $bean->email;
        $user->name = $bean->name ?? '';
        $user->password = $bean->password ?? '';
        $user->createdAt = isset($bean->created_at)
            ? new \DateTime($bean->created_at)
            : new \DateTime();

        return $user;
    }
}
