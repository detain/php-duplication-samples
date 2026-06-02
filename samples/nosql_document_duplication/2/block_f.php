<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MarkLogic.
 * Demonstrates "Insert with auto-generated ID" in MarkLogic style.
 */
final class MarkLogicDocumentRepository
{
    private \MarkLogic\Client $client;

    public function __construct(\MarkLogic\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @param string $collection
     * @return string
     */
    public function insert(array $data, string $collection = '/documents'): string
    {
        $documentId = '/documents/' . uniqid('doc_', true) . '.json';
        $data['createdAt'] = date('c');
        $data['updatedAt'] = date('c');

        try {
            $this->client->insert($documentId, $data, [
                'collection' => $collection,
            ]);
            return $documentId;
        } catch (\MarkLogic\Client\Exception\ServerException $e) {
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
        try {
            $document = $this->client->read($id);
            return $document->getContent();
        } catch (\MarkLogic\Client\Exception\NotFoundException $e) {
            return null;
        } catch (\MarkLogic\Client\Exception\ServerException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
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
        $data['updatedAt'] = date('c');

        try {
            $this->client->write($id, $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete document.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        try {
            $this->client->delete($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
