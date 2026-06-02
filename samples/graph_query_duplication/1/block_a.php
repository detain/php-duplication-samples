<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Neo4j.
 * Demonstrates "Find related nodes" in Neo4j Cypher style.
 */
final class Neo4jGraphRepository
{
    private \Neo4j\Client $client;

    public function __construct(\Neo4j\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Find nodes related to a given node.
     *
     * @param string $nodeLabel
     * @param string $nodeId
     * @param string $relationshipType
     * @param string $direction
     * @return array
     */
    public function findRelated(
        string $nodeLabel,
        string $nodeId,
        string $relationshipType,
        string $direction = 'OUTGOING'
    ): array {
        $arrow = $direction === 'OUTGOING' ? '-->' : '<--';

        $query = "
            MATCH (n:{$nodeLabel} {id: \$nodeId}){$arrow}(related)
            RETURN related
        ";

        $result = $this->client->run($query, ['nodeId' => $nodeId]);

        $nodes = [];

        foreach ($result as $record) {
            $nodes[] = $record->get('related')->values();
        }

        return $nodes;
    }

    /**
     * Find nodes with multiple hops.
     *
     * @param string $startLabel
     * @param string $startId
     * @param array $relationshipTypes
     * @return array
     */
    public function findWithHops(string $startLabel, string $startId, array $relationshipTypes): array
    {
        $hops = count($relationshipTypes);
        $pattern = 'n';
        $params = ['startId' => $startId];

        for ($i = 0; $i < $hops; $i++) {
            $rel = $relationshipTypes[$i];
            $pattern .= "-[:{$rel}]->(n" . ($i + 1) . ")";
        }

        $query = "
            MATCH ({$pattern})
            WHERE n.id = \$startId
            RETURN n{$hops}
        ";

        $result = $this->client->run($query, $params);

        $nodes = [];

        foreach ($result as $record) {
            $nodes[] = $record->get('n' . $hops)->values();
        }

        return $nodes;
    }

    /**
     * Create relationship between nodes.
     *
     * @param string $fromLabel
     * @param string $fromId
     * @param string $toLabel
     * @param string $toId
     * @param string $relationshipType
     * @param array $properties
     * @return bool
     */
    public function createRelationship(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        string $relationshipType,
        array $properties = []
    ): bool {
        $query = "
            MATCH (a:{$fromLabel} {id: \$fromId})
            MATCH (b:{$toLabel} {id: \$toId})
            CREATE (a)-[r:{$relationshipType}]->(b)
            SET r = \$properties
            RETURN r
        ";

        try {
            $result = $this->client->run($query, [
                'fromId' => $fromId,
                'toId' => $toId,
                'properties' => $properties,
            ]);

            return $result->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete node and its relationships.
     *
     * @param string $label
     * @param string $nodeId
     * @return bool
     */
    public function deleteWithRelations(string $label, string $nodeId): bool
    {
        $query = "
            MATCH (n:{$label} {id: \$nodeId})
            DETACH DELETE n
        ";

        try {
            $this->client->run($query, ['nodeId' => $nodeId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
