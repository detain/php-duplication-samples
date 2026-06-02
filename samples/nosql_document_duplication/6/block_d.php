<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using DynamoDB.
 * Demonstrates "Aggregation (count, sum, avg)" in DynamoDB style.
 */
final class DynamoDBAggregationRepository
{
    private \Aws\DynamoDb\DynamoDbClient $client;
    private string $tableName;

    public function __construct(\Aws\DynamoDb\DynamoDbClient $client, string $tableName = 'documents')
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    /**
     * Count documents matching conditions.
     *
     * @param array $conditions
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->buildFilterExpression($conditions);

        $params = [
            'TableName' => $this->tableName,
            'Select' => 'COUNT',
        ];

        if (!empty($query['filter'])) {
            $params['FilterExpression'] = $query['filter'];
            $params['ExpressionAttributeValues'] = $query['values'];
            $params['ExpressionAttributeNames'] = $query['names'];
        }

        $result = $this->client->scan($params);

        return $result['Count'] ?? 0;
    }

    /**
     * Sum a field across matching documents.
     *
     * @param string $field
     * @param array $conditions
     * @return float
     */
    public function sum(string $field, array $conditions = []): float
    {
        $query = $this->buildFilterExpression($conditions);

        $params = [
            'TableName' => $this->tableName,
            'ProjectionExpression' => $field,
        ];

        if (!empty($query['filter'])) {
            $params['FilterExpression'] = $query['filter'];
            $params['ExpressionAttributeValues'] = $query['values'];
            $params['ExpressionAttributeNames'] = $query['names'];
        }

        $result = $this->client->scan($params);

        $total = 0.0;
        foreach ($result['Items'] as $item) {
            if (isset($item[$field])) {
                $total += (float) ($item[$field]['N'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Average a field across matching documents.
     *
     * @param string $field
     * @param array $conditions
     * @return float
     */
    public function avg(string $field, array $conditions = []): float
    {
        $query = $this->buildFilterExpression($conditions);

        $params = [
            'TableName' => $this->tableName,
            'ProjectionExpression' => $field,
        ];

        if (!empty($query['filter'])) {
            $params['FilterExpression'] = $query['filter'];
            $params['ExpressionAttributeValues'] = $query['values'];
            $params['ExpressionAttributeNames'] = $query['names'];
        }

        $result = $this->client->scan($params);

        $count = count($result['Items']);

        if ($count === 0) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($result['Items'] as $item) {
            if (isset($item[$field])) {
                $total += (float) ($item[$field]['N'] ?? 0);
            }
        }

        return $total / $count;
    }

    /**
     * Get multiple aggregations at once.
     *
     * @param string $field
     * @param array $conditions
     * @return array
     */
    public function getStats(string $field, array $conditions = []): array
    {
        $query = $this->buildFilterExpression($conditions);

        $params = [
            'TableName' => $this->tableName,
            'ProjectionExpression' => $field,
        ];

        if (!empty($query['filter'])) {
            $params['FilterExpression'] = $query['filter'];
            $params['ExpressionAttributeValues'] = $query['values'];
            $params['ExpressionAttributeNames'] = $query['names'];
        }

        $result = $this->client->scan($params);

        $count = count($result['Items']);
        $total = 0.0;
        $min = PHP_FLOAT_MAX;
        $max = PHP_FLOAT_MIN;

        foreach ($result['Items'] as $item) {
            if (isset($item[$field])) {
                $value = (float) ($item[$field]['N'] ?? 0);
                $total += $value;
                $min = min($min, $value);
                $max = max($max, $value);
            }
        }

        if ($count === 0) {
            return ['count' => 0, 'total' => 0.0, 'average' => 0.0, 'min' => 0.0, 'max' => 0.0];
        }

        return [
            'count' => $count,
            'total' => $total,
            'average' => $total / $count,
            'min' => $min,
            'max' => $max,
        ];
    }

    private function buildFilterExpression(array $conditions): array
    {
        $filter = [];
        $values = [];
        $names = [];
        $i = 1;

        foreach ($conditions as $field => $condition) {
            $attrName = '#attr' . $i;
            $attrValue = ':val' . $i;
            $names[$attrName] = $field;

            if (is_array($condition) && isset($condition['operator'])) {
                $operator = $condition['operator'];
                $value = $condition['value'];
                $values[$attrValue] = ['N' => (string) $value];

                $filter[] = match ($operator) {
                    'eq' => "{$attrName} = {$attrValue}",
                    'gt' => "{$attrName} > {$attrValue}",
                    'gte' => "{$attrName} >= {$attrValue}",
                    'lt' => "{$attrName} < {$attrValue}",
                    'lte' => "{$attrName} <= {$attrValue}",
                    default => "{$attrName} = {$attrValue}",
                };
            } else {
                $values[$attrValue] = ['N' => (string) $condition];
                $filter[] = "{$attrName} = {$attrValue}";
            }

            $i++;
        }

        return [
            'filter' => implode(' AND ', $filter),
            'values' => $values,
            'names' => $names,
        ];
    }
}
