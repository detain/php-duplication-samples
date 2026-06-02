<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Memgraph.
 * Demonstrates "Pattern matching" in Memgraph Cypher style.
 */
final class MemgraphPatternRepository
{
    private \Memgraph\Client $client;

    public function __construct(\Memgraph\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Find nodes by pattern.
     *
     * @param string $pattern
     * @param array $params
     * @return array
     */
    public function findByPattern(string $pattern, array $params = []): array
    {
        $query = "MATCH {$pattern} RETURN n";

        $result = $this->client->run($query, $params);

        $nodes = [];

        foreach ($result as $record) {
            $node = $record->get('n');
            $nodes[] = [
                'id' => $node->id(),
                'properties' => $node->properties(),
            ];
        }

        return $nodes;
    }

    /**
     * Find connected nodes by pattern.
     *
     * @param string $nodeLabel
     * @param string $nodeId
     * @param string $relationshipPattern
     * @return array
     */
    public function findConnected(
        string $nodeLabel,
        string $nodeId,
        string $relationshipPattern
    ): array {
        $query = "
            MATCH (n:{$nodeLabel} {id: \$nodeId}){$relationshipPattern}(connected)
            RETURN connected
        ";

        $result = $this->client->run($query, ['nodeId' => $nodeId]);

        $nodes = [];

        foreach ($result as $record) {
            $node = $record->get('connected');
            $nodes[] = [
                'id' => $node->id(),
                'properties' => $node->properties(),
            ];
        }

        return $nodes;
    }
}
