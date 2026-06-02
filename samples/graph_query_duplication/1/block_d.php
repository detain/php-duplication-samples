<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using JanusGraph.
 * Demonstrates "Find related nodes" in JanusGraph Gremlin style.
 */
final class JanusGraphRepository
{
    private \JanusGraph\Client $client;

    public function __construct(\JanusGraph\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Find nodes related to a given node.
     *
     * @param string $nodeLabel
     * @param string $nodeId
     * @param string $edgeLabel
     * @param string $direction
     * @return array
     */
    public function findRelated(
        string $nodeLabel,
        string $nodeId,
        string $edgeLabel,
        string $direction = 'OUTGOING'
    ): array {
        $g = $this->client->traversal();

        $traversal = $g->V()
            ->has($nodeLabel, 'nodeId', $nodeId);

        if ($direction === 'OUTGOING') {
            $traversal = $traversal->out($edgeLabel);
        } else {
            $traversal = $traversal->in($edgeLabel);
        }

        $results = $traversal->toList();

        $nodes = [];

        foreach ($results as $vertex) {
            $nodes[] = $vertex->values();
        }

        return $nodes;
    }

    /**
     * Find nodes with multiple hops.
     *
     * @param string $startLabel
     * @param string $startId
     * @param array $edgeLabels
     * @return array
     */
    public function findWithHops(string $startLabel, string $startId, array $edgeLabels): array
    {
        $g = $this->client->traversal();

        $traversal = $g->V()
            ->has($startLabel, 'nodeId', $startId);

        foreach ($edgeLabels as $edgeLabel) {
            $traversal = $traversal->out($edgeLabel);
        }

        $results = $traversal->toList();

        $nodes = [];

        foreach ($results as $vertex) {
            $nodes[] = $vertex->values();
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
     * @param string $edgeLabel
     * @param array $properties
     * @return bool
     */
    public function createRelationship(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        string $edgeLabel,
        array $properties = []
    ): bool {
        $g = $this->client->traversal();

        try {
            $fromVertex = $g->V()
                ->has($fromLabel, 'nodeId', $fromId)
                ->next();

            $toVertex = $g->V()
                ->has($toLabel, 'nodeId', $toId)
                ->next();

            $g->addE($edgeLabel)
                ->from($fromVertex)
                ->to($toVertex)
                ->property($properties)
                ->iterate();

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
        $g = $this->client->traversal();

        try {
            $vertex = $g->V()
                ->has($label, 'nodeId', $nodeId)
                .next();

            $g->V($vertex)
                ->drop()
                .iterate();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
