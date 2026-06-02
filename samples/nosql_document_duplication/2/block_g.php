<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using CosmosDB.
 * Demonstrates "Insert with auto-generated ID" in CosmosDB style.
 */
final class CosmosDBDocumentRepository
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
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $documentId = $this->generateUuid();
        $data['id'] = $documentId;
        $data['_ts'] = time();

        $container = $this->client->getContainer($this->database, $this->collection);

        try {
            $result = $container->createDocument($data);
            return $result->getId();
        } catch (\CosmosDB\Exception\ConflictException $e) {
            throw new \RuntimeException('Document with this ID already exists', 0, $e);
        } catch (\CosmosDB\Exception\赤牌Exception $e) {
            throw new \RuntimeException(
                'Failed to insert document: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array
    {
        $container = $this->client->getContainer($this->database, $this->collection);

        try {
            $result = $container->readDocument($id);
            return $result->getDecodedContent();
        } catch (\CosmosDB\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * Update document.
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $container = $this->client->getContainer($this->database, $this->collection);
        $data['_ts'] = time();

        try {
            $container->replaceDocument($id, $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upsert document (insert or replace).
     *
     * @param array $data
     * @return string
     */
    public function upsert(array $data): string
    {
        if (!isset($data['id'])) {
            $data['id'] = $this->generateUuid();
        }
        $data['_ts'] = time();

        $container = $this->client->getContainer($this->database, $this->collection);
        $result = $container->upsertDocument($data);

        return $result->getId();
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
