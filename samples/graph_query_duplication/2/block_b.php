<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Dgraph.
 * Demonstrates "Create and delete nodes" in Dgraph GraphQL+- style.
 */
final class DgraphNodeRepository
{
    private \Dgraph\Client $client;

    public function __construct(\Dgraph\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a node.
     *
     * @param string $type
     * @param array $properties
     * @return string
     */
    public function create(string $type, array $properties): string
    {
        $mutation = array_merge($properties, [
            'dgraph.type' => $type,
        ]);

        try {
            $result = $this->client->mutate($mutation);
            $uid = $result->getUids()[$type] ?? null;

            return $uid ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Find node by UID.
     *
     * @param string $uid
     * @return array|null
     */
    public function findById(string $uid): ?array
    {
        $query = 'query findById($uid: string) {
            node(func: uid($uid)) {
                uid
                expand(_all_)
            }
        }';

        try {
            $result = $this->client->query($query, ['uid' => $uid]);
            $nodes = $result->getJson()['node'] ?? [];

            return $nodes[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update a node.
     *
     * @param string $uid
     * @param array $properties
     * @return bool
     */
    public function update(string $uid, array $properties): bool
    {
        $mutation = array_merge(['uid' => $uid], $properties);

        try {
            $this->client->mutate($mutation);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a node.
     *
     * @param string $uid
     * @return bool
     */
    public function delete(string $uid): bool
    {
        $mutation = [
            'uid' => $uid,
            'dgraph.type' => null,
        ];

        try {
            $this->client->mutate($mutation);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
