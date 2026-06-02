<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Neo4j.
 * Demonstrates "BFS traversal" in Neo4j Cypher style.
 */
final class Neo4jBfsRepository
{
    private \Neo4j\Client $client;

    public function __construct(\Neo4j\Client $client)
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
            CALL db.traverse(start, '', \$relationshipType, \$maxDepth)
            YIELD node
            RETURN node
        ";

        $result = $this->client->run($query, [
            'startId' => $startId,
            'relationshipType' => $relationshipType,
            'maxDepth' => $maxDepth,
        ]);

        $nodes = [];

        foreach ($result as $record) {
            $nodes[] = $record->get('node')->values();
        }

        return $nodes;
    }
}
