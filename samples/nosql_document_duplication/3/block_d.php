<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using DynamoDB.
 * Demonstrates "Update with optimistic locking" in DynamoDB style.
 */
final class DynamoDBOptimisticLockRepository
{
    private \Aws\DynamoDb\DynamoDbClient $client;
    private string $tableName;

    public function __construct(\Aws\DynamoDb\DynamoDbClient $client, string $tableName = 'documents')
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    /**
     * Update with optimistic locking using version attribute.
     *
     * @param string $id
     * @param array $data
     * @param int $expectedVersion
     * @return bool
     * @throws \RuntimeException
     */
    public function updateWithLock(string $id, array $data, int $expectedVersion): bool
    {
        $data['version'] = $expectedVersion + 1;
        $data['updatedAt'] = (int) (microtime(true) * 1000);

        try {
            $this->client->updateItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $id],
                ],
                'UpdateExpression' => 'SET #v = :newV, updatedAt = :ua, attribute_exists(#v)',
                'ConditionExpression' => '#v = :expectedV',
                'ExpressionAttributeNames' => [
                    '#v' => 'version',
                ],
                'ExpressionAttributeValues' => [
                    ':newV' => ['N' => (string) ($expectedVersion + 1)],
                    ':expectedV' => ['N' => (string) $expectedVersion],
                    ':ua' => ['N' => (string) $data['updatedAt']],
                ],
            ]);

            return true;
        } catch (\Aws\DynamoDb\Exception\DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                $current = $this->findById($id);
                throw new \RuntimeException(
                    'Version conflict: expected ' . $expectedVersion .
                    ', current ' . ($current['version'] ?? 'unknown')
                );
            }
            throw new \RuntimeException('Update failed: ' . $e->getMessage(), 0, $e);
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
     * Insert document with version.
     *
     * @param array $data
     * @return string
     */
    public function insert(array $data): string
    {
        $documentId = $this->generateUuid();
        $data['id'] = $documentId;
        $data['version'] = 1;
        $data['createdAt'] = (int) (microtime(true) * 1000);
        $data['updatedAt'] = $data['createdAt'];

        $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => $this->marshalItems($data),
        ]);

        return $documentId;
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
