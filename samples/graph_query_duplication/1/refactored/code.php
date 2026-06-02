<?php
declare(strict_types=1);

namespace App\Database\Graph\Refactored;

/**
 * Graph relationship direction.
 */
enum RelationshipDirection: string
{
    case OUTGOING = 'OUTGOING';
    case INCOMING = 'INCOMING';
    case BOTH = 'BOTH';
}

/**
 * Graph traversal result.
 */
final readonly class GraphTraversalResult
{
    public function __construct(
        public array $nodes,
        public int $total,
    ) {}

    public static function empty(): self
    {
        return new self([], 0);
    }
}

/**
 * Interface for graph repositories.
 */
interface GraphRepositoryInterface
{
    /**
     * Find nodes related to a given node.
     *
     * @param string $nodeLabel
     * @param string $nodeId
     * @param string $relationshipType
     * @param RelationshipDirection $direction
     * @return array
     */
    public function findRelated(
        string $nodeLabel,
        string $nodeId,
        string $relationshipType,
        RelationshipDirection $direction = RelationshipDirection::OUTGOING
    ): array;

    /**
     * Find nodes with multiple hops.
     *
     * @param string $startLabel
     * @param string $startId
     * @param array $relationshipTypes
     * @return array
     */
    public function findWithHops(string $startLabel, string $startId, array $relationshipTypes): array;

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
    ): bool;

    /**
     * Delete node and its relationships.
     *
     * @param string $label
     * @param string $nodeId
     * @return bool
     */
    public function deleteWithRelations(string $label, string $nodeId): bool;
}

/**
 * Abstract base for graph repositories.
 */
abstract class AbstractGraphRepository implements GraphRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findRelated(
        string $nodeLabel,
        string $nodeId,
        string $relationshipType,
        RelationshipDirection $direction = RelationshipDirection::OUTGOING
    ): array {
        return $this->doFindRelated($nodeLabel, $nodeId, $relationshipType, $direction->value);
    }

    /**
     * {@inheritdoc}
     */
    public function findWithHops(string $startLabel, string $startId, array $relationshipTypes): array
    {
        return $this->doFindWithHops($startLabel, $startId, $relationshipTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function createRelationship(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        string $relationshipType,
        array $properties = []
    ): bool {
        return $this->doCreateRelationship($fromLabel, $fromId, $toLabel, $toId, $relationshipType, $properties);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWithRelations(string $label, string $nodeId): bool
    {
        return $this->doDeleteWithRelations($label, $nodeId);
    }

    abstract protected function doFindRelated(
        string $nodeLabel,
        string $nodeId,
        string $relationshipType,
        string $direction
    ): array;

    abstract protected function doFindWithHops(
        string $startLabel,
        string $startId,
        array $relationshipTypes
    ): array;

    abstract protected function doCreateRelationship(
        string $fromLabel,
        string $fromId,
        string $toLabel,
        string $toId,
        string $relationshipType,
        array $properties
    ): bool;

    abstract protected function doDeleteWithRelations(string $label, string $nodeId): bool;
}

/**
 * Factory for graph repositories.
 */
final class GraphRepositoryFactory
{
    public static function create(string $type, array $config = []): GraphRepositoryInterface
    {
        return match ($type) {
            'neo4j' => new \App\Database\Graph\Neo4jGraphRepository($config['client']),
            'neptune' => new \App\Database\Graph\NeptuneGraphRepository($config['client']),
            'dgraph' => new \App\Database\Graph\DgraphGraphRepository($config['client']),
            'janusgraph' => new \App\Database\Graph\JanusGraphRepository($config['client']),
            'memgraph' => new \App\Database\Graph\MemgraphGraphRepository($config['client']),
            default => throw new \RuntimeException("Unknown graph type: {$type}"),
        };
    }
}
