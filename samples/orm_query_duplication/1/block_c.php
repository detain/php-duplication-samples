<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * Repository for User entity using Propel ORM.
 * Demonstrates "Find user by email" operation in Propel ActiveRecord style.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     * @return UserPropel|null The found user or null if not exists
     * @throws RuntimeException If query execution fails
     */
    public function findByEmail(string $email): ?UserPropel
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return UserQuery::create()
                ->filterByEmail($normalizedEmail)
                ->findOne($this->connection);
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to find user by email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find a user by email with eager loading of related objects.
     *
     * @param string $email The email to search for
     * @param array<string> $relations Relations to eager load
     * @return UserPropel|null
     */
    public function findByEmailWith(string $email, array $relations = []): ?UserPropel
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $query = UserQuery::create()
                ->filterByEmail($normalizedEmail);

            foreach ($relations as $relation) {
                $query->joinWith($relation);
            }

            return $query->findOne($this->connection);
        } catch (PropelException $e) {
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
     * @return UserPropel
     */
    public function findOrCreateByEmail(string $email, array $defaults = []): UserPropel
    {
        if ($email === '') {
            throw new RuntimeException('Email cannot be empty');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        try {
            $user = UserQuery::create()
                ->filterByEmail($normalizedEmail)
                ->findOne($this->connection);

            if ($user !== null) {
                return $user;
            }

            $user = new UserPropel();
            $user->setEmail($normalizedEmail);

            foreach ($defaults as $key => $value) {
                $setter = "set" . str_replace('_', '', ucwords($key, '_'));
                if (method_exists($user, $setter)) {
                    $user->$setter($value);
                }
            }

            $user->save($this->connection);

            return $user;
        } catch (PropelException $e) {
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
            return UserQuery::create()
                ->filterByEmail($normalizedEmail)
                ->exists($this->connection);
        } catch (PropelException $e) {
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
     * @return \Propel\Runtime\Collection\ObjectCollection
     */
    public function findByEmailDomain(string $domain): \Propel\Runtime\Collection\ObjectCollection
    {
        $normalizedDomain = mb_strtolower(trim($domain));

        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain cannot be empty');
        }

        try {
            $partialEmail = "%@{$normalizedDomain}";

            return UserQuery::create()
                ->filterByEmail($partialEmail, \Propel\Runtime\Util\PropelCriterion::ILIKE)
                ->find($this->connection);
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to find users by domain: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get user by primary key with additional validation.
     *
     * @param int $id The user ID
     * @param string $email Email to verify ownership
     * @return UserPropel|null
     */
    public function findByIdAndEmail(int $id, string $email): ?UserPropel
    {
        $normalizedEmail = mb_strtolower(trim($email));

        try {
            return UserQuery::create()
                ->filterById($id)
                ->filterByEmail($normalizedEmail)
                ->findOne($this->connection);
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to find user by id and email: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
