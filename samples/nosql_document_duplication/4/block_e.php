<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using DynamoDB.
 * Demonstrates "Delete with cascade" in DynamoDB style.
 */
final class DynamoDBCascadeDeleteRepository
{
    private \Aws\DynamoDb\DynamoDbClient $client;
    private string $tableName;

    public function __construct(\Aws\DynamoDb\DynamoDbClient $client, string $tableName = 'documents')
    {
        $this->client = $client;
        $this->tableName = $tableName;
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
            $targetTable = $rule['table'];
            $targetField = $rule['field'];
            $targetValue = $document[$targetField] ?? null;

            if ($targetValue !== null) {
                if ($rule['cascade'] === 'delete') {
                    $this->client->deleteItem([
                        'TableName' => $targetTable,
                        'Key' => [
                            $rule['key'] ?? 'id' => ['S' => $targetValue],
                        ],
                    ]);
                } elseif ($rule['cascade'] === 'nullify') {
                    $this->client->updateItem([
                        'TableName' => $targetTable,
                        'Key' => [
                            $rule['key'] ?? 'id' => ['S' => $targetValue],
                        ],
                        'UpdateExpression' => 'SET ' . $targetField . ' = :null',
                        'ExpressionAttributeValues' => [
                            ':null' => ['NULL' => true],
                        ],
                    ]);
                }
            }
        }

        $this->client->deleteItem([
            'TableName' => $this->tableName,
            'Key' => [
                'id' => ['S' => $id],
            ],
        ]);

        return true;
    }

    /**
     * Find document by ID.
     */
    public function findById(string $id): ?array
    {
        $result = $this->client->getItem([
            'TableName' => $this->tableName,
            'Key' => [
                'id' => ['S' => $id],
            ],
        ]);

        if (!isset($result['Item'])) {
            return null;
        }

        $item = [];
        foreach ($result['Item'] as $key => $value) {
            if (isset($value['S'])) {
                $item[$key] = $value['S'];
            } elseif (isset($value['N'])) {
                $item[$key] = (int) $value['N'];
            }
        }

        return $item;
    }
}
