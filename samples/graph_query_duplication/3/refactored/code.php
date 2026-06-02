<?php
declare(strict_types=1);

namespace App\Database\Graph\Refactored;

interface PathRepositoryInterface
{
    public function shortestPath(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        string $relationshipType
    ): array;

    public function allPaths(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        int $maxHops = 5
    ): array;
}

final class PathRepositoryFactory
{
    public static function create(string $type, array $config = []): PathRepositoryInterface
    {
        return match ($type) {
            'neo4j' => new \App\Database\Graph\Neo4jPathRepository($config['client']),
            'memgraph' => new \App\Database\Graph\MemgraphPathRepository($config['client']),
            default => throw new \RuntimeException("Unknown graph type: {$type}"),
        };
    }
}
