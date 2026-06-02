<?php
declare(strict_types=1);

namespace App\Database\Yii;

use App\Models\User as UserYii;
use yii\db\ActiveRecord;
use yii\db\Exception;
use RuntimeException;

/**
 * Repository for User entity using Yii2 ActiveRecord.
 * Demonstrates "Find user by email" operation in Yii2 AR style.
 */
final class YiiUserRepository
{
    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     * @return UserYii|null The found user or null if not exists
     * @throws RuntimeException If query execution fails
     */
    public function findByEmail(string $email): ?UserYii
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return UserYii::find()
                ->where(['email' => $normalizedEmail])
                ->one();
        } catch (Exception $e) {
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
     * @return UserYii|null
     */
    public function findByEmailWith(string $email, array $relations = []): ?UserYii
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return UserYii::find()
                ->where(['email' => $normalizedEmail])
                ->with($relations)
                ->one();
        } catch (Exception $e) {
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
     * @return UserYii The found user
     * @throws RuntimeException If user not found
     */
    public function findByEmailOrFail(string $email): UserYii
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        $user = UserYii::find()
            ->where(['email' => $normalizedEmail])
            ->one();

        if ($user === null) {
            throw new RuntimeException("User with email '{$normalizedEmail}' not found");
        }

        return $user;
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
            return UserYii::find()
                ->where(['email' => $normalizedEmail])
                ->exists();
        } catch (Exception $e) {
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
     * @return array<UserYii>
     */
    public function findByEmailDomain(string $domain): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        try {
            return UserYii::find()
                ->where(['LIKE', 'email', "@{$normalizedDomain}", false])
                ->all();
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get user count by domain.
     *
     * @param string $domain The domain to count by
     * @return int
     */
    public function countByDomain(string $domain): int
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        try {
            return (int) UserYii::find()
                ->where(['LIKE', 'email', "@{$normalizedDomain}", false])
                ->count();
        } catch (Exception $e) {
            throw new RuntimeException(
                'Failed to count users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find users with pagination support.
     *
     * @param string $domain The domain to filter by
     * @param int $page Page number (1-based)
     * @param int $pageSize Items per page
     * @return array{users: array<UserYii>, total: int, page: int, pageSize: int}
     */
    public function findPaginatedByDomain(string $domain, int $page = 1, int $pageSize = 20): array
    {
        $normalizedDomain = mb_strtolower(trim($domain));
        $offset = ($page - 1) * $pageSize;

        $query = UserYii::find()
            ->where(['LIKE', 'email', "@{$normalizedDomain}", false])
            ->orderBy(['id' => SORT_DESC]);

        $total = $query->count();
        $users = $query->offset($offset)->limit($pageSize)->all();

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }
}
