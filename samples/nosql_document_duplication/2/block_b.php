<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using DynamoDB.
 * Demonstrates "Insert with auto-generated ID" in DynamoDB style.
 */
final class DynamoDBDocumentRepository
{
    private \Aws\DynamoDb\DynamoDbClient $client;
    private string $tableName;

    public function __construct(\Aws\DynamoDb\DynamoDbClient $client, string $tableName = 'documents')
    {
        $this->client = $client;
        $this->tableName = $tableName;
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
        $data['createdAt'] = (int) (microtime(true) * 1000);
        $data['updatedAt'] = (int) (microtime(true) * 1000);

        $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => $this->marshalItems($data),
        ]);

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
        $result = $this->client->getItem([
            'TableName' => $this->tableName,
            'Key' => [
                'id' => ['S' => $id],
            ],
        ]);

        if (!isset($result['Item'])) {
            return null;
        }

        return $this->unmarshalItems($result['Item']);
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
        $data['updatedAt'] = (int) (microtime(true) * 1000);

        try {
            $updateExpression = 'SET ';
            $expressionAttributeNames = [];
            $expressionAttributeValues = [];
            $updates = [];

            foreach ($data as $key => $value) {
                $attrName = '#' . $key;
                $attrValue = ':' . $key;
                $updates[] = "{$attrName} = {$attrValue}";
                $expressionAttributeNames[$attrName] = $key;
                $expressionAttributeValues[$attrValue] = $this->marshalValue($value);
            }

            $updateExpression .= implode(', ', $updates);

            $this->client->updateItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $id],
                ],
                'UpdateExpression' => $updateExpression,
                'ExpressionAttributeNames' => $expressionAttributeNames,
                'ExpressionAttributeValues' => $expressionAttributeValues,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
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

    private function marshalItems(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            $result[$key] = $this->marshalValue($value);
        }
        return $result;
    }

    private function marshalValue($value): array
    {
        if (is_string($value)) {
            return ['S' => $value];
        } elseif (is_int($value)) {
            return ['N' => (string) $value];
        } elseif (is_float($value)) {
            return ['N' => (string) $value];
        } elseif (is_bool($value)) {
            return ['BOOL' => $value];
        } elseif (is_array($value)) {
            return ['L' => array_map(fn($v) => $this->marshalValue($v), $value)];
        } elseif (is_null($value)) {
            return ['NULL' => true];
        }
        return ['S' => (string) $value];
    }

    private function unmarshalItems(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            $result[$key] = $this->unmarshalValue($value);
        }
        return $result;
    }

    private function unmarshalValue(array $value)
    {
        if (isset($value['S'])) {
            return $value['S'];
        } elseif (isset($value['N'])) {
            return is_float($value['N'] + 0) ? (float) $value['N'] : (int) $value['N'];
        } elseif (isset($value['BOOL'])) {
            return $value['BOOL'];
        } elseif (isset($value['NULL'])) {
            return null;
        } elseif (isset($value['L'])) {
            return array_map(fn($v) => $this->unmarshalValue($v), $value['L']);
        }
        return null;
    }
}
