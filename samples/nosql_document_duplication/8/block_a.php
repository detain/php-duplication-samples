<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MongoDB.
 * Demonstrates "Raw query fallback" in MongoDB style.
 */
final class MongoRawQueryRepository
{
    private \MongoDB\Database $database;
    private string $collectionName;

    public function __construct(\MongoDB\Database $database, string $collectionName = 'documents')
    {
        $this->database = $database;
        $this->collectionName = $collectionName;
    }

    /**
     * Execute raw aggregation pipeline.
     *
     * @param array $pipeline
     * @return array
     */
    public function rawQuery(array $pipeline): array
    {
        $collection = $this->database->selectCollection($this->collectionName);

        $cursor = $collection->aggregate($pipeline);

        $results = [];

        foreach ($cursor as $document) {
            $results[] = (array) $document;
        }

        return $results;
    }

    /**
     * Execute raw command.
     *
     * @param string $command
     * @param array $arguments
     * @return array
     */
    public function rawCommand(string $command, array $arguments = []): array
    {
        $commandDoc = array_merge([$command => 1], $arguments);

        $result = $this->database->command($commandDoc)->toArray();

        return $result[0] ?? [];
    }
}
