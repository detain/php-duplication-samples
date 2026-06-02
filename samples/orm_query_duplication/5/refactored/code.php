<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for user search operations.
 */
interface SearchableUserRepositoryInterface
{
    /**
     * Search users with multiple WHERE conditions.
     *
     * @param array<string, mixed> $criteria
     * @param int $limit
     * @param int $offset
     * @return array<UserDTO>
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array;

    /**
     * Search users with OR conditions.
     *
     * @param array<string, mixed> $criteria
     * @return array<UserDTO>
     */
    public function searchUsersOr(array $criteria): array;

    /**
     * Find users by status with count.
     *
     * @param string $status
     * @return SearchResultDTO
     */
    public function findByStatus(string $status): SearchResultDTO;

    /**
     * Advanced search with complex criteria.
     *
     * @param array<string, mixed> $criteria
     * @return array<UserDTO>
     */
    public function advancedSearch(array $criteria): array;
}

/**
 * Data transfer object for search results.
 */
final readonly class SearchResultDTO
{
    public function __construct(
        public array $users,
        public int $count,
    ) {}

    public function toArray(): array
    {
        return [
            'users' => array_map(fn(UserDTO $u) => $u->toArray(), $this->users),
            'count' => $this->count,
        ];
    }
}

/**
 * Search criteria builder.
 */
final class SearchCriteria
{
    private array $conditions = [];
    private int $limit = 20;
    private int $offset = 0;
    private array $orderBy = [];

    public function __construct(private string $operator = 'AND') {}

    public function addCondition(string $field, mixed $value, string $operator = '='): self
    {
        if ($value !== null && $value !== '') {
            $this->conditions[$field] = [
                'value' => $value,
                'operator' => $operator,
            ];
        }

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[$field] = $direction;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'conditions' => $this->conditions,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'orderBy' => $this->orderBy,
            'operator' => $this->operator,
        ];
    }
}

/**
 * Abstract base search repository.
 */
abstract class AbstractSearchableUserRepository implements SearchableUserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function searchUsers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        $this->validateCriteria($criteria);

        return $this->doSearch($criteria, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function searchUsersOr(array $criteria): array
    {
        $this->validateCriteria($criteria);

        return $this->doSearchOr($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(string $status): SearchResultDTO
    {
        $users = $this->doSearch(['status' => $status], 1000, 0);

        return new SearchResultDTO($users, count($users));
    }

    /**
     * {@inheritdoc}
     */
    public function advancedSearch(array $criteria): array
    {
        $this->validateAdvancedCriteria($criteria);

        return $this->doAdvancedSearch($criteria);
    }

    /**
     * Validate basic search criteria.
     *
     * @param array<string, mixed> $criteria
     */
    protected function validateCriteria(array $criteria): void
    {
        $allowedFields = ['name', 'email', 'status', 'createdAfter'];

        foreach (array_keys($criteria) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                throw new \InvalidArgumentException("Unknown search field: {$field}");
            }
        }
    }

    /**
     * Validate advanced search criteria.
     *
     * @param array<string, mixed> $criteria
     */
    protected function validateAdvancedCriteria(array $criteria): void
    {
        $allowedFields = ['name', 'email', 'status', 'statusIN', 'createdBetween', 'roleIds'];

        foreach (array_keys($criteria) as $field) {
            if (!in_array($field, $allowedFields, true)) {
                throw new \InvalidArgumentException("Unknown search field: {$field}");
            }
        }
    }

    /**
     * Perform the actual search.
     *
     * @param array<string, mixed> $criteria
     * @param int $limit
     * @param int $offset
     * @return array<UserDTO>
     */
    abstract protected function doSearch(array $criteria, int $limit, int $offset): array;

    /**
     * Perform the actual OR search.
     *
     * @param array<string, mixed> $criteria
     * @return array<UserDTO>
     */
    abstract protected function doSearchOr(array $criteria): array;

    /**
     * Perform advanced search.
     *
     * @param array<string, mixed> $criteria
     * @return array<UserDTO>
     */
    abstract protected function doAdvancedSearch(array $criteria): array;
}

/**
 * Factory for creating searchable repositories.
 */
final class SearchableUserRepositoryFactory
{
    public static function create(string $type, array $config = []): SearchableUserRepositoryInterface
    {
        return match ($type) {
            'doctrine' => new \App\Database\Doctrine\DoctrineUserRepository(
                $config['entity_manager']
            ),
            'eloquent' => new \App\Database\Eloquent\EloquentUserRepository(),
            'propel' => new \App\Database\Propel\PropelUserRepository(
                $config['connection'] ?? null
            ),
            'cycle' => new \App\Database\Cycle\CycleUserRepository(
                $config['entity_manager']
            ),
            'yii' => new \App\Database\Yii\YiiUserRepository(),
            'cake' => new \App\Database\Cake\CakeUserRepository(
                $config['table']
            ),
            'redbean' => new \App\Database\RedBean\RedBeanUserRepository(
                $config['database'] ?? null
            ),
            default => throw new \RuntimeException("Unknown repository type: {$type}"),
        };
    }
}
