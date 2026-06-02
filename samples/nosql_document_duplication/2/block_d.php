<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Generic document repository using Solr.
 * Demonstrates "Insert with auto-generated ID" in Solr style.
 */
final class SolrDocumentRepository
{
    private \Solarium\Client $client;
    private string $core;

    public function __construct(\Solarium\Client $client, string $core = 'documents')
    {
        $this->client = $client;
        $this->core = $core;
    }

    /**
     * Insert document with auto-generated ID.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $documentId = uniqid('solr_', true);
        $data['id'] = $documentId;
        $data['created_at'] = date('Y-m-d\TH:i:s\Z');
        $data['updated_at'] = date('Y-m-d\TH:i:s\Z');

        $client = $this->client;
        $update = $client->createUpdate();
        $doc = $update->createDocument();

        foreach ($data as $field => $value) {
            $doc->addField($field, $value);
        }

        $update->addDocument($doc);
        $update->addCommit();

        $result = $client->update($update);

        if ($result->getResponse()->getStatusCode() !== 0) {
            throw new \RuntimeException('Failed to insert document into Solr');
        }

        return $documentId;
    }

    /**
     * Find document by ID.
     *
     * @param string $id
     * @return array|null
     */
    public function findById(string $id): ?array
    {
        $query = $this->client->createSelect();
        $query->setQuery('id:' . $id);
        $query->setRows(1);

        $result = $this->client->select($query);

        if ($result->getNumFound() === 0) {
            return null;
        }

        $doc = $result->getDocuments()[0];
        return $doc->getFields();
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
        $data['id'] = $id;
        $data['updated_at'] = date('Y-m-d\TH:i:s\Z');

        $update = $this->client->createUpdate();
        $doc = $update->createDocument();

        foreach ($data as $field => $value) {
            $doc->addField($field, $value);
        }

        $update->addDocument($doc);
        $update->addCommit();

        try {
            $result = $this->client->update($update);
            return $result->getResponse()->getStatusCode() === 0;
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
        $update = $this->client->createUpdate();
        $update->addDeleteById($id);
        $update->addCommit();

        try {
            $result = $this->client->update($update);
            return $result->getResponse()->getStatusCode() === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
