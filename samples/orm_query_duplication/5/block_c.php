<?php
declare(strict_types=1);

namespace App\Database\Propel;

use App\Model\User as UserPropel;
use App\Model\UserQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;

/**
 * User repository using Propel ORM.
 * Demonstrates "Search users with WHERE clause" operation.
 */
final class PropelUserRepository
{
    private \Propel\Runtime\Connection\ConnectionInterface $connection;

    public function __construct(?\Propel\Runtime\Connection\ConnectionInterface $connection = null)
    {
        $this->connection = $connection ?? Propel::getWriteConnection('default');
    }

    /**
     * Search users with multiple WHERE conditions.
     *
     * @param array{name?: string, email?: string, status?: string} $criteria
     * @param int $limit
     * @param int $offset
     * @return array<UserPropel>
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        $query = UserQuery::create();

        if (!empty($criteria['name'])) {
            $query->filterByName('%' . $criteria['name'] . '%', \Propel\Runtime\Util\PropelCriterion::LIKE);
        }

        if (!empty($criteria['email'])) {
            $query->filterByEmail('%' . $criteria['email'] . '%', \Propel\Runtime\Util\PropelCriterion::LIKE);
        }

        if (!empty($criteria['status'])) {
            $query->filterByStatus($criteria['status']);
        }

        if (!empty($criteria['createdAfter'])) {
            $query->filterByCreatedAt($criteria['createdAfter'], \Propel\Runtime\Util\PropelCriterion::GREATER_THAN);
        }

        $query->orderByCreatedAt('desc')
              ->limit($limit)
              ->offset($offset);

        try {
            return $query->find($this->connection);
        } catch (PropelException $e) {
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
     * @param array<string, string> $criteria
     * @return array<UserPropel>
     */
    public function searchUsersOr(array $criteria): array
    {
        $query = UserQuery::create();

        $c1 = $query->getNewCriterion(
            UserPeer::NAME,
            '%' . ($criteria['name'] ?? '') . '%',
            \Propel\Runtime\Util\PropelCriterion::LIKE
        );

        $c2 = $query->getNewCriterion(
            UserPeer::EMAIL,
            '%' . ($criteria['email'] ?? '') . '%',
            \Propel\Runtime\Util\PropelCriterion::LIKE
        );

        $c1->addOr($c2);

        $query->where($c1);
        $query->orderByCreatedAt('desc');

        try {
            return $query->find($this->connection);
        } catch (PropelException $e) {
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
     * @return array{users: array<UserPropel>, count: int}
     */
    public function findByStatus(string $status): array
    {
        $count = UserQuery::create()
            ->filterByStatus($status)
            ->count($this->connection);

        $users = UserQuery::create()
            ->filterByStatus($status)
            ->orderByCreatedAt('desc')
            ->find($this->connection);

        return ['users' => $users, 'count' => $count];
    }

    /**
     * Advanced search with complex criteria.
     *
     * @param array{
     *     name?: string,
     *     email?: string,
     *     status?: string[],
     *     createdBetween?: array{start: \DateTimeInterface, end: \DateTimeInterface}
     * } $criteria
     * @return array<UserPropel>
     */
    public function advancedSearch(array $criteria): array
    {
        $query = UserQuery::create();

        if (!empty($criteria['name'])) {
            $query->filterByName('%' . $criteria['name'] . '%', \Propel\Runtime\Util\PropelCriterion::LIKE);
        }

        if (!empty($criteria['email'])) {
            $query->filterByEmail('%' . $criteria['email'] . '%', \Propel\Runtime\Util\PropelCriterion::LIKE);
        }

        if (!empty($criteria['status']) && is_array($criteria['status'])) {
            $query->filterByStatus($criteria['status']);
        }

        if (!empty($criteria['createdBetween'])) {
            $query->filterByCreatedAt($criteria['createdBetween']['start']);
            $query->filterByCreatedAt($criteria['createdBetween']['end'], \Propel\Runtime\Util\PropelCriterion::LESS_THAN);
        }

        $query->orderByCreatedAt('desc');

        try {
            return $query->find($this->connection);
        } catch (PropelException $e) {
            throw new RuntimeException(
                'Failed to search users: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
