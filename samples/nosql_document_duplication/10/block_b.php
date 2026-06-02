<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Firestore.
 * Demonstrates "SQL-like query fallback" in Firestore style.
 */
final class FirestoreRawQueryRepository
{
    private \Google\Cloud\Firestore\FirestoreClient $client;
    private string $collection;

    public function __construct(\Google\Cloud\Firestore\FirestoreClient $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
    }

    /**
     * Execute raw SQL-like query via streaming.
     *
     * @param string $query
     * @return array
     */
    public function rawQuery(string $query): array
    {
        $db = $this->client->database();
        $collection = $db->collection($this->collection);

        $documents = $collection->limit(100)->documents();

        $results = [];

        foreach ($documents as $document) {
            $results[] = $document->data();
        }

        return $results;
    }
}
