<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Batch insert" in Elasticsearch style.
 */
final class ElasticsearchBatchInsertRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Batch insert multiple documents.
     *
     * @param array $documents
     * @return array Inserted IDs
     */
    public function batchInsert(array $documents): array
    {
        $now = date('Y-m-d H:i:s');
        $params = ['body' => []];

        foreach ($documents as $document) {
            unset($document['_id']);

            $params['body'][] = [
                'index' => [
                    '_index' => $this->index,
                ],
            ];

            $document['createdAt'] = $now;
            $document['updatedAt'] = $now;

            $params['body'][] = $document;
        }

        $response = $this->client->bulk($params);

        $insertedIds = [];

        if (isset($response['items'])) {
            foreach ($response['items'] as $item) {
                if (isset($item['index']['_id'])) {
                    $insertedIds[] = $item['index']['_id'];
                }
            }
        }

        return $insertedIds;
    }

    /**
     * Batch upsert documents.
     *
     * @param array $documents
     * @param string $idField
     * @return int Number of modified
     */
    public function batchUpsert(array $documents, string $idField = 'id'): int
    {
        $now = date('Y-m-d H:i:s');
        $params = ['body' => []];

        foreach ($documents as $document) {
            $id = $document[$idField] ?? null;

            if ($id === null) {
                continue;
            }

            unset($document['_id']);

            $params['body'][] = [
                'update' => [
                    '_index' => $this->index,
                    '_id' => $id,
                ],
            ];

            $params['body'][] = [
                'doc' => array_merge($document, ['updatedAt' => $now]),
                'doc_as_upsert' => true,
            ];
        }

        if (empty($params['body'])) {
            return 0;
        }

        $response = $this->client->bulk($params);

        $count = 0;

        if (isset($response['items'])) {
            foreach ($response['items'] as $item) {
                if (isset($item['update']['result'])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Batch delete documents.
     *
     * @param array $ids
     * @return int Number of deleted
     */
    public function batchDelete(array $ids): int
    {
        $params = ['body' => []];

        foreach ($ids as $id) {
            $params['body'][] = [
                'delete' => [
                    '_index' => $this->index,
                    '_id' => $id,
                ],
            ];
        }

        $response = $this->client->bulk($params);

        $count = 0;

        if (isset($response['items'])) {
            foreach ($response['items'] as $item) {
                if (isset($item['delete']['result']) && $item['delete']['result'] === 'deleted') {
                    $count++;
                }
            }
        }

        return $count;
    }
}
