<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "AQL query fallback" in ArangoDB style.
 */
final class ArangoDBRawQueryRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Execute raw AQL query.
     *
     * @param string $aql
     * @param array $bindVars
     * @return array
     */
    public function rawQuery(string $aql, array $bindVars = []): array
    {
        $cursor = $this->client->query($aql, $bindVars);

        $results = [];

        foreach ($cursor as $document) {
            $results[] = $document;
        }

        return $results;
    }
}
