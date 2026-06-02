<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

/**
 * Raw query interface for flexible queries.
 */
interface RawQueryRepositoryInterface
{
    /**
     * Execute raw query.
     *
     * @param array $query
     * @return array
     */
    public function rawQuery(array $query): array;

    /**
     * Execute raw command.
     *
     * @param string $command
     * @param array $arguments
     * @return array
     */
    public function rawCommand(string $command, array $arguments = []): array;
}

final class RawQueryRepositoryFactory
{
    public static function create(string $type, array $config = []): RawQueryRepositoryInterface
    {
        return match ($type) {
            'mongodb' => new \App\Database\NoSQL\MongoRawQueryRepository($config['database']),
            'elasticsearch' => new \App\Database\NoSQL\ElasticsearchRawQueryRepository($config['client']),
            default => throw new \RuntimeException("Unknown type: {$type}"),
        };
    }
}
