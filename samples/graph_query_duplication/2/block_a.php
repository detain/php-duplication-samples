<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Neo4j.
 * Demonstrates "Create and delete nodes" in Neo4j Cypher style.
 */
final class Neo4jNodeRepository
{
    private \Neo4j\Client $client;

    public function __construct(\Neo4j\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a node.
     *
     * @param string $label
     * @param array $properties
     * @return string
     */
    public function create(string $label, array $properties): string
    {
        $query = "
            CREATE (n:{$label})
            SET n = \$properties
            RETURN id(n) AS nodeId
        ";

        $result = $this->client->run($query, ['properties' => $properties]);

        return $result->first()->get('nodeId');
    }

    /**
     * Find node by ID.
     *
     * @param string $label
     * @param string $nodeId
     * @return array|null
     */
    public function findById(string $label, string $nodeId): ?array
    {
        $query = "
            MATCH (n:{$label})
            WHERE id(n) = \$nodeId
            RETURN n
        ";

        $result = $this->client->run($query, ['nodeId' => $nodeId]);

        if ($result->count() === 0) {
            return null;
        }

        return $result->first()->get('n')->values();
    }

    /**
     * Update a node.
     *
     * @param string $label
     * @param string $nodeId
     * @param array $properties
     * @return bool
     */
    public function update(string $label, string $nodeId, array $properties): bool
    {
        $query = "
            MATCH (n:{$label})
            WHERE id(n) = \$nodeId
            SET n += \$properties
            RETURN n
        ";

        try {
            $result = $this->client->run($query, [
                'nodeId' => $nodeId,
                'properties' => $properties,
            ]);

            return $result->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a node.
     *
     * @param string $label
     * @param string $nodeId
     * @return bool
     */
    public function delete(string $label, string $nodeId): bool
    {
        $query = "
            MATCH (n:{$label})
            WHERE id(n) = \$nodeId
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
