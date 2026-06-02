<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Memgraph.
 * Demonstrates "Find related nodes" in Memgraph Cypher style.
 */
final class MemgraphGraphRepository
{
    private \Memgraph\Client $client;

    public function __construct(\Memgraph\Client $client)
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
            $related = $record->get('related');
            $nodes[] = [
                'id' => $related->id(),
                'properties' => $related->properties(),
            ];
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
        $pattern = 'n';
        $returnVar = 'n' . (count($relationshipTypes) - 1);

        for ($i = 0; $i < count($relationshipTypes); $i++) {
            $rel = $relationshipTypes[$i];
            $pattern .= "-[:{$rel}]->(n{$i})";
            $returnVar = "n{$i}";
        }

        $query = "
            MATCH ({$pattern})
            WHERE n0.id = \$startId
            RETURN {$returnVar}
        ";

        $result = $this->client->run($query, ['startId' => $startId]);

        $nodes = [];

        foreach ($result as $record) {
            $node = $record->get($returnVar);
            $nodes[] = [
                'id' => $node->id(),
                'properties' => $node->properties(),
            ];
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
