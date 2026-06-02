<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Amazon Neptune.
 * Demonstrates "Find related nodes" in Neptune Gremlin style.
 */
final class NeptuneGraphRepository
{
    private \Gremlin\Client $client;

    public function __construct(\Gremlin\Client $client)
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
        $directionTraversal = $direction === 'OUTGOING'
            ? '.out(relationshipType)'
            : '.in(relationshipType)';

        $query = sprintf(
            "g.V().has('%s', 'id', nodeId)%s",
            $nodeLabel,
            $directionTraversal
        );

        $result = $this->client->submit($query, ['nodeId' => $nodeId]);

        $nodes = [];

        foreach ($result as $vertex) {
            $nodes[] = (array) $vertex;
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
        $hops = implode(',', array_map(fn($r) => "'{$r}'", $relationshipTypes));

        $query = sprintf(
            "g.V().has('%s', 'id', startId).repeat(out(%s)).times(%d).path()",
            $startLabel,
            $hops,
            count($relationshipTypes)
        );

        $result = $this->client->submit($query, ['startId' => $startId]);

        $nodes = [];

        foreach ($result as $path) {
            $nodes[] = (array) $path;
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
        $query = sprintf(
            "g.V().has('%s', 'id', fromId).addE('%s').to(g.V().has('%s', 'id', toId))",
            $fromLabel,
            $relationshipType,
            $toLabel
        );

        try {
            $this->client->submit($query, [
                'fromId' => $fromId,
                'toId' => $toId,
            ]);
            return true;
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
        $query = sprintf(
            "g.V().has('%s', 'id', nodeId).drop()",
            $label
        );

        try {
            $this->client->submit($query, ['nodeId' => $nodeId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
