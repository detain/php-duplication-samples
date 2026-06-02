<?php
declare(strict_types=1);

namespace App\Database;

/**
 * Common interface for paginated user operations.
 */
interface PaginatedUserRepositoryInterface
{
    /**
     * Get paginated users.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return PaginatedResultDTO
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): PaginatedResultDTO;

    /**
     * Get paginated users with cursor-based pagination.
     *
     * @param int $cursor
     * @param int $limit
     * @return CursorPaginatedResultDTO
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): CursorPaginatedResultDTO;

    /**
     * Get paginated users sorted by activity.
     *
     * @param int $page
     * @param int $perPage
     * @return PaginatedResultDTO
     */
    public function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): PaginatedResultDTO;
}

/**
 * Paginated result data transfer object.
 */
final readonly class PaginatedResultDTO
{
    public function __construct(
        public array $users,
        public int $total,
        public int $page,
        public int $perPage,
        public int $totalPages,
    ) {}

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function toArray(): array
    {
        return [
            'users' => array_map(fn(UserDTO $u) => $u->toArray(), $this->users),
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'totalPages' => $this->totalPages,
            'hasNextPage' => $this->hasNextPage(),
            'hasPreviousPage' => $this->hasPreviousPage(),
        ];
    }
}

/**
 * Cursor paginated result data transfer object.
 */
final readonly class CursorPaginatedResultDTO
{
    public function __construct(
        public array $users,
        public ?int $nextCursor,
        public bool $hasMore,
    ) {}

    public function toArray(): array
    {
        return [
            'users' => array_map(fn(UserDTO $u) => $u->toArray(), $this->users),
            'nextCursor' => $this->nextCursor,
            'hasMore' => $this->hasMore,
        ];
    }
}

/**
 * Pagination type enum.
 */
enum PaginationType
{
    case OFFSET;
    case CURSOR;
    case KEYSET;
}

/**
 * Abstract base paginated repository.
 */
abstract class AbstractPaginatedUserRepository implements PaginatedUserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPaginatedUsers(int $page = 1, int $perPage = 20, array $filters = []): PaginatedResultDTO
    {
        $this->validatePaginationParams($page, $perPage);

        $result = $this->doGetPaginatedUsers($page, $perPage, $filters);

        return new PaginatedResultDTO(
            users: $result['users'],
            total: $result['total'],
            page: $result['page'],
            perPage: $result['perPage'],
            totalPages: $result['totalPages'],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersCursorPaginated(int $cursor = 0, int $limit = 20): CursorPaginatedResultDTO
    {
        $this->validateCursorParams($cursor, $limit);

        $result = $this->doGetUsersCursorPaginated($cursor, $limit);

        return new CursorPaginatedResultDTO(
            users: $result['users'],
            nextCursor: $result['nextCursor'],
            hasMore: $result['hasMore'],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedUsersByActivity(int $page = 1, int $perPage = 20): PaginatedResultDTO
    {
        $this->validatePaginationParams($page, $perPage);

        $result = $this->doGetPaginatedUsersByActivity($page, $perPage);

        return new PaginatedResultDTO(
            users: $result['users'],
            total: $result['total'],
            page: $result['page'],
            perPage: $result['perPage'],
            totalPages: $result['totalPages'],
        );
    }

    /**
     * Validate offset-based pagination parameters.
     *
     * @param int $page
     * @param int $perPage
     */
    protected function validatePaginationParams(int $page, int $perPage): void
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('Page must be at least 1');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('Per page must be between 1 and 100');
        }
    }

    /**
     * Validate cursor-based pagination parameters.
     *
     * @param int $cursor
     * @param int $limit
     */
    protected function validateCursorParams(int $cursor, int $limit): void
    {
        if ($cursor < 0) {
            throw new \InvalidArgumentException('Cursor must be non-negative');
        }

        if ($limit < 1 || $limit > 100) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100');
        }
    }

    /**
     * Perform actual paginated query.
     *
     * @param int $page
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return array{users: array, total: int, page: int, perPage: int, totalPages: int}
     */
    abstract protected function doGetPaginatedUsers(int $page, int $perPage, array $filters): array;

    /**
     * Perform actual cursor paginated query.
     *
     * @param int $cursor
     * @param int $limit
     * @return array{users: array, nextCursor: int|null, hasMore: bool}
     */
    abstract protected function doGetUsersCursorPaginated(int $cursor, int $limit): array;

    /**
     * Perform actual activity-based paginated query.
     *
     * @param int $page
     * @param int $perPage
     * @return array{users: array, total: int, page: int, perPage: int, totalPages: int}
     */
    abstract protected function doGetPaginatedUsersByActivity(int $page, int $perPage): array;
}

/**
 * Factory for creating paginated repositories.
 */
final class PaginatedUserRepositoryFactory
{
    public static function create(string $type, array $config = []): PaginatedUserRepositoryInterface
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
