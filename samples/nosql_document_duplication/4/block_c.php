<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Couchbase.
 * Demonstrates "Delete with cascade" in Couchbase style.
 */
final class CouchbaseCascadeDeleteRepository
{
    private \Couchbase\Client $client;
    private string $bucket;

    public function __construct(\Couchbase\Client $client, string $bucket = 'documents')
    {
        $this->client = $client;
        $this->bucket = $bucket;
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
        $collection = $this->client->bucket($this->bucket)->defaultCollection();
        $cluster = $this->client->bucket($this->bucket)->cluster();

        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        foreach ($cascadeRules as $rule) {
            $targetKeyspace = $rule['keyspace'];
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                if ($rule['cascade'] === 'delete') {
                    $cluster->query(
                        "DELETE FROM {$targetKeyspace} WHERE {$targetField} = ?",
                        [$targetValue]
                    );
                } elseif ($rule['cascade'] === 'nullify') {
                    $cluster->query(
                        "UPDATE {$targetKeyspace} SET {$targetField} = NULL WHERE {$targetField} = ?",
                        [$targetValue]
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
        $collection = $this->client->bucket($this->bucket)->defaultCollection();

        try {
            $result = $collection->get($id);
            return $result->content();
        } catch (\Couchbase\Exception\DocumentNotFoundException $e) {
            return null;
        }
    }
}
