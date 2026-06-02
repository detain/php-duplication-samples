<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Neo4j.
 * Demonstrates "Path finding" in Neo4j Cypher style.
 */
final class Neo4jPathRepository
{
    private \Neo4j\Client $client;

    public function __construct(\Neo4j\Client $client)
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

        return $result->first()->get('path')->values();
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
            $paths[] = $record->get('path')->values();
        }

        return $paths;
    }
}
