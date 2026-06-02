<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CouchDB.
 * Demonstrates "Mango query fallback" in CouchDB style.
 */
final class CouchDBRawQueryRepository
{
    private \CouchDB\Client $client;
    private string $database;

    public function __construct(\CouchDB\Client $client, string $database = 'documents')
    {
        $this->client = $client;
        $this->database = $database;
    }

    /**
     * Execute raw Mango query.
     *
     * @param array $query
     * @return array
     */
    public function rawQuery(array $query): array
    {
        $response = $this->client->post($this->database . '/_find', $query);

        return $response['docs'] ?? [];
    }
}
