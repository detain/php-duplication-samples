<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Search criteria builder.
 */
final class SearchCriteria
{
    /**
     * @param array<string, mixed> $conditions
     * @param array<string, string> $sort
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(
        private array $conditions = [],
        private array $sort = [],
        private ?int $limit = null,
        private ?int $offset = null,
    ) {}

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function addCondition(string $field, mixed $value, string $operator = 'eq'): self
    {
        $this->conditions[$field] = [
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    public function addSort(string $field, int $direction = SORT_ASC): self
    {
        $this->sort[$field] = $direction;
        return $this;
    }

    public function setPagination(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }
}

/**
 * Search result with pagination info.
 */
final readonly class SearchResult
{
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function getTotalPages(): int
    {
        return $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0;
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }
}

/**
 * Interface for search repositories.
 */
interface SearchRepositoryInterface
{
    /**
     * Search documents.
     *
     * @param SearchCriteria $criteria
     * @return array
     */
    public function search(SearchCriteria $criteria): array;

    /**
     * Search with pagination.
     *
     * @param SearchCriteria $criteria
     * @param int $page
     * @param int $perPage
     * @return SearchResult
     */
    public function searchPaginated(SearchCriteria $criteria, int $page = 1, int $perPage = 20): SearchResult;
}

/**
 * Factory for search repositories.
 */
final class SearchRepositoryFactory
{
    public static function create(string $type, array $config = []): SearchRepositoryInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoSearchRepository(
                $config['database']
            ),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchSearchRepository(
                $config['client']
            ),
            'arangodb' => new \App\Database\NoSQL\ArangoDBSearchRepository(
                $config['client']
            ),
            'dynamodb' => new \App\Database\NoSQL\DynamoDBSearchRepository(
                $config['client']
            ),
            default => throw new \RuntimeException("Unknown document type: {$type}"),
        };
    }
}
