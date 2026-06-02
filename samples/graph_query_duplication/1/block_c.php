<?php
declare(strict_types=1);

namespace App\Database\Graph;

/**
 * Graph repository using Dgraph.
 * Demonstrates "Find related nodes" in Dgraph GraphQL+- style.
 */
final class DgraphGraphRepository
{
    private \Dgraph\Client $client;

    public function __construct(\Dgraph\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Find nodes related to a given node.
     *
     * @param string $nodeType
     * @param string $nodeId
     * @param string $predicate
     * @param string $direction
     * @return array
     */
    public function findRelated(
        string $nodeType,
        string $nodeId,
        string $predicate,
        string $direction = 'OUTGOING'
    ): array {
        $query = $direction === 'OUTGOING'
            ? 'query findRelated($id: string) {
                node(func: eq(id, $id)) {
                    expand(_) {
                        ' . $predicate . ' {
                            uid
                            id
                            expand(_all_)
                        }
                    }
                }
            }'
            : 'query findRelated($id: string) {
                node(func: eq(id, $id)) {
                    ~' . $predicate . ' {
                        uid
                        id
                        expand(_all_)
                    }
                }
            }';

        $result = $this->client->query($query, ['id' => $nodeId]);

        return $result->getJson()['node'] ?? [];
    }

    /**
     * Find nodes with multiple hops.
     *
     * @param string $startType
     * @param string $startId
     * @param array $predicates
     * @return array
     */
    public function findWithHops(string $startType, string $startId, array $predicates): array
    {
        $blocks = [];
        $uid = 'uid';

        foreach ($predicates as $i => $predicate) {
            $blocks[] = $predicate . ' {
                uid
                id
                expand(_all_)
            }';
            $uid = $predicate;
        }

        $query = 'query findWithHops($id: string) {
            start(func: eq(id, $id)) {
                id
                expand(_all_)
                ' . implode("\n", $blocks) . '
            }
        }';

        $result = $this->client->query($query, ['id' => $startId]);

        return $result->getJson()['start'] ?? [];
    }

    /**
     * Create relationship between nodes.
     *
     * @param string $fromUid
     * @param string $toUid
     * @param string $predicate
     * @param array $properties
     * @return bool
     */
    public function createRelationship(
        string $fromUid,
        string $toUid,
        string $predicate,
        array $properties = []
    ): bool {
        $mutation = [
            'uid' => $fromUid,
            $predicate => [
                'uid' => $toUid,
            ],
        ];

        $mutation = array_merge($mutation, $properties);

        try {
            $this->client->mutate($mutation);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete node and its relationships.
     *
     * @param string $nodeUid
     * @return bool
     */
    public function deleteWithRelations(string $nodeUid): bool
    {
        $mutation = [
            'uid' => $nodeUid,
            'dgraph.type' => null,
        ];

        foreach ($this->getPredicates($nodeUid) as $predicate) {
            $mutation[$predicate] = null;
        }

        try {
            $this->client->mutate($mutation);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getPredicates(string $uid): array
    {
        $query = 'query getPredicates($uid: string) {
            node(func: uid($uid)) {
                expand(_all_)
            }
        }';

        $result = $this->client->query($query, ['uid' => $uid]);
        $data = $result->getJson()['node'][0] ?? [];

        return array_keys($data);
    }
}
