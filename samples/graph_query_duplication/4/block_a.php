<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Neo4j.
 * Demonstrates "Pattern matching" in Neo4j Cypher style.
 */
final class Neo4jPatternRepository
{
    private \Neo4j\Client $client;

    public function __construct(\Neo4j\Client $client)
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
            $nodes[] = $record->get('n')->values();
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
            $nodes[] = $record->get('connected')->values();
        }

        return $nodes;
    }
}
