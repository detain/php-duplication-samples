<?php
declare(strict_types=1);

namespace App\Database\Graph\Refactored;

interface BfsRepositoryInterface
{
    public function bfs(string $startLabel, string $startId, string $relationshipType, int $maxDepth = 10): array;
}

final class BfsRepositoryFactory
{
    public static function create(string $type, array $config = []): BfsRepositoryInterface
    {
        return match ($type) {
            'neo4j' => new \App\Database\Graph\Neo4jBfsRepository($config['client']),
            'memgraph' => new \App\Database\Graph\MemgraphBfsRepository($config['client']),
            default => throw new \RuntimeException("Unknown graph type: {$type}"),
        };
    }
}
