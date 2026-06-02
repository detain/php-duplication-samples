<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CosmosDB.
 * Demonstrates "Delete with cascade" in CosmosDB style.
 */
final class CosmosDBCascadeDeleteRepository
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
     * Delete document with cascade to related documents.
     *
     * @param string $id
     * @param array $cascadeRules Rules for cascade deletion
     * @return bool
     */
    public function deleteWithCascade(string $id, array $cascadeRules = []): bool
    {
        $container = $this->client->getContainer($this->database, $this->collection);
        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        foreach ($cascadeRules as $rule) {
            $targetContainer = $this->client->getContainer($this->database, $rule['collection']);
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                if ($rule['cascade'] === 'delete') {
                    $query = "SELECT * FROM c WHERE c.{$targetField} = '{$targetValue}'";
                    $results = $targetContainer->query($query);

                    foreach ($results as $item) {
                        $targetContainer->delete($item['id']);
                    }
                } elseif ($rule['cascade'] === 'nullify') {
                    $query = "SELECT * FROM c WHERE c.{$targetField} = '{$targetValue}'";
                    $results = $targetContainer->query($query);

                    foreach ($results as $item) {
                        $item[$targetField] = null;
                        $targetContainer->replace($item['id'], $item);
                    }
                }
            }
        }

        $container->delete($id);

        return true;
    }

    /**
     * Find document by ID.
     */
    public function findById(string $id): ?array
    {
        $container = $this->client->getContainer($this->database, $this->collection);

        try {
            $result = $container->read($id);
            return $result->getDecodedContent();
        } catch (\CosmosDB\Exception\NotFoundException $e) {
            return null;
        }
    }
}
