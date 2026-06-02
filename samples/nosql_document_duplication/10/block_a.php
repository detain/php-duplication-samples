<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CosmosDB.
 * Demonstrates "SQL query fallback" in CosmosDB style.
 */
final class CosmosDBRawQueryRepository
{
    private \CosmosDB\Client $client;
    private string $database;
    private string $collection;

    public function __construct(
        \CosmosDB\Client $client,
        string $database = 'mydb',
        string $collection = 'documents'
    ) {
        $this->client = $client;
        $this->database = $database;
        $this->collection = $collection;
    }

    /**
     * Execute raw SQL query.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function rawQuery(string $sql, array $params = []): array
    {
        $container = $this->client->getContainer($this->database, $this->collection);

        $results = $container->query($sql, $params);

        $documents = [];

        foreach ($results as $item) {
            $documents[] = $item;
        }

        return $documents;
    }
}
