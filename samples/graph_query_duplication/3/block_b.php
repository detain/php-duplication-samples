<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Memgraph.
 * Demonstrates "Path finding" in Memgraph Cypher style.
 */
final class MemgraphPathRepository
{
    private \Memgraph\Client $client;

    public function __construct(\Memgraph\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Find shortest path between nodes.
     *
     * @param string $fromLabel
     * @param string $fromId
     * @param string $toLabel
     * @param string $toId
     * @param string $relationshipType
     * @return array
     */
    public function shortestPath(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        string $relationshipType
    ): array {
        $query = "
            MATCH (a:{$fromLabel} {id: \$fromId})
            MATCH (b:{$toLabel} {id: \$toId})
            MATCH path = shortestPath((a)-[:{$relationshipType}*]->(b))
            RETURN path
        ";

        $result = $this->client->run($query, [
            'fromId' => $fromId,
            'toId' => $toId,
        ]);

        if ($result->count() === 0) {
            return [];
        }

        $path = $result->first()->get('path');
        return [
            'nodes' => $path->nodes(),
            'relationships' => $path->relationships(),
        ];
    }

    /**
     * Find all paths between nodes.
     *
     * @param string $fromLabel
     * @param string $fromId
     * @param string $toLabel
     * @param string $toId
     * @param int $maxHops
     * @return array
     */
    public function allPaths(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        int $maxHops = 5
    ): array {
        $query = "
            MATCH (a:{$fromLabel} {id: \$fromId})
            MATCH (b:{$toLabel} {id: \$toId})
            MATCH path = (a)-[*1..{$maxHops}]-(b)
            RETURN path
        ";

        $result = $this->client->run($query, [
            'fromId' => $fromId,
            'toId' => $toId,
        ]);

        $paths = [];

        foreach ($result as $record) {
            $path = $record->get('path');
            $paths[] = [
                'nodes' => $path->nodes(),
                'relationships' => $path->relationships(),
            ];
        }

        return $paths;
    }
}
