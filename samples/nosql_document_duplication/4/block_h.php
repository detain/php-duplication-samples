<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using MarkLogic.
 * Demonstrates "Delete with cascade" in MarkLogic style.
 */
final class MarkLogicCascadeDeleteRepository
{
    private \MarkLogic\Client $client;

    public function __construct(\MarkLogic\Client $client)
    {
        $this->client = $client;
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
            $targetCollection = $rule['collection'];
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                $query = "fn:collection('{$targetCollection}')[.{$targetField} = '{$targetValue}']";

                if ($rule['cascade'] === 'delete') {
                    $this->client->query(
                        "xdmp:document-delete(fn:doc($query))"
                    );
                } elseif ($rule['cascade'] === 'nullify') {
                    $this->client->query(
                        "for \$doc in {$query} " .
                        "let \$new := map:put(\$doc, '{$targetField}', ()) " .
                        "return xdmp:document-insert(fn:document-uri(\$doc), \$new)"
                    );
                }
            }
        }

        $this->client->delete($id);

        return true;
    }

    /**
     * Find document by ID.
     */
    public function findById(string $id): ?array
    {
        try {
            $document = $this->client->read($id);
            return $document->getContent();
        } catch (\Exception $e) {
            return null;
        }
    }
}
