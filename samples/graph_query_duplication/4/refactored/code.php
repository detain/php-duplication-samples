<?php
declare(strict_types=1);

namespace App\Database\Graph\Refactored;

interface PatternRepositoryInterface
{
    public function findByPattern(string $pattern, array $params = []): array;
    public function findConnected(string $nodeLabel, string $nodeId, string $relationshipPattern): array;
}

final class PatternRepositoryFactory
{
    public static function create(string $type, array $config = []): PatternRepositoryInterface
    {
        return match ($type) {
            'neo4j' => new \App\Database\Graph\Neo4jPatternRepository($config['client']),
            'memgraph' => new \App\Database\Graph\MemgraphPatternRepository($config['client']),
            default => throw new \RuntimeException("Unknown graph type: {$type}"),
        };
    }
}
