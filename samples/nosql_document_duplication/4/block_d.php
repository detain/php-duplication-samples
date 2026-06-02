<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "Delete with cascade" in ArangoDB style.
 */
final class ArangoDBCascadeDeleteRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
    {
        $this->client = $client;
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
        $collection = $this->client->collection($this->collection);
        $graph = $this->client->graph('default');

        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        foreach ($cascadeRules as $rule) {
            $targetCollection = $rule['collection'];
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                if ($rule['cascade'] === 'delete') {
                    $edgeCollection = $this->client->collection($targetCollection);
                    $edgeCollection->removeByExample([$targetField => $targetValue]);
                } elseif ($rule['cascade'] === 'nullify') {
                    $edgeCollection = $this->client->collection($targetCollection);
                    $edgeCollection->updateByExample(
                        [$targetField => $targetValue],
                        [$targetField => null]
                    );
                }
            }
        }

        try {
            $collection->remove($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Find document by ID.
     */
    public function findById(string $id): ?array
    {
        $collection = $this->client->collection($this->collection);

        try {
            $document = $collection->get($id);
            return $document->getAll();
        } catch (\Exception $e) {
            return null;
        }
    }
}
