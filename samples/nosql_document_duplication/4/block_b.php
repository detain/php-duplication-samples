<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using Elasticsearch.
 * Demonstrates "Delete with cascade" in Elasticsearch style.
 */
final class ElasticsearchCascadeDeleteRepository
{
    private \Elastic\Elasticsearch\Client $client;
    private string $index;

    public function __construct(\Elastic\Elasticsearch\Client $client, string $index = 'documents')
    {
        $this->client = $client;
        $this->index = $index;
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
        $document = $this->findById($id);

        if ($document === null) {
            return false;
        }

        foreach ($cascadeRules as $rule) {
            $targetIndex = $rule['index'];
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                if ($rule['cascade'] === 'delete') {
                    $this->client->deleteByQuery([
                        'index' => $targetIndex,
                        'body' => [
                            'query' => [
                                'term' => [$targetField => $targetValue],
                            ],
                        ],
                    ]);
                } elseif ($rule['cascade'] === 'nullify') {
                    $this->client->updateByQuery([
                        'index' => $targetIndex,
                        'body' => [
                            'query' => [
                                'term' => [$targetField => $targetValue],
                            ],
                            'script' => [
                                'source' => 'ctx._source.' . $targetField . ' = null',
                                'lang' => 'painless',
                            ],
                        ],
                    ]);
                }
            }
        }

        $this->client->delete([
            'index' => $this->index,
            'id' => $id,
        ]);

        return true;
    }

    /**
     * Find document by ID.
     */
    public function findById(string $id): ?array
    {
        try {
            $response = $this->client->get([
                'index' => $this->index,
                'id' => $id,
            ]);

            if (!$response['found']) {
                return null;
            }

            return $response['_source'];
        } catch (\Exception $e) {
            return null;
        }
    }
}
