<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Memgraph.
 * Demonstrates "BFS traversal" in Memgraph Cypher style.
 */
final class MemgraphBfsRepository
{
    private \Memgraph\Client $client;

    public function __construct(\Memgraph\Client $client)
    {
        $this->client = $client;
    }

    /**
     * BFS traversal from a node.
     *
     * @param string $startLabel
     * @param string $startId
     * @param string $relationshipType
     * @param int $maxDepth
     * @return array
     */
    public function bfs(string $startLabel, string $startId, string $relationshipType, int $maxDepth = 10): array
    {
        $query = "
            MATCH (start:{$startLabel} {id: \$startId})
            CALL dijkstra(start, connected, 'weight', \$relationshipType)
            YIELD path
            RETURN path
        ";

        $result = $this->client->run($query, [
            'startId' => $startId,
            'relationshipType' => $relationshipType,
            'maxDepth' => $maxDepth,
        ]);

        $paths = [];

        foreach ($result as $record) {
            $paths[] = $record->get('path')->nodes();
        }

        return $paths;
    }
}
