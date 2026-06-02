<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Raw query fallback" in Elasticsearch style.
 */
final class ElasticsearchRawQueryRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Execute raw query.
     *
     * @param array $query
     * @return array
     */
    public function rawQuery(array $query): array
    {
        $response = $this->client->search([
            'index' => $this->index,
            'body' => $query,
        ]);

        return $response->asArray();
    }

    /**
     * Execute raw command.
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function rawCommand(string $endpoint, array $params = []): array
    {
        $response = $this->client->cluster()->health();

        return $response->asArray();
    }
}
