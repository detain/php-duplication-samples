<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using DynamoDB.
 * Demonstrates "Search with WHERE clause" in DynamoDB style.
 */
final class DynamoDBSearchRepository
{
    private \Aws\DynamoDb\DynamoDbClient $client;
    private string $tableName;

    public function __construct(\Aws\DynamoDb\DynamoDbClient $client, string $tableName = 'documents')
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    /**
     * Search documents with WHERE clause.
     *
     * @param array $conditions
     * @param array $options
     * @return array
     */
    public function search(array $conditions, array $options = []): array
    {
        $query = $this->buildQuery($conditions);

        $params = [
            'TableName' => $this->tableName,
            'FilterExpression' => $query['filter'],
            'ExpressionAttributeValues' => $query['values'],
            'ExpressionAttributeNames' => $query['names'],
        ];

        if (isset($options['limit'])) {
            $params['Limit'] = $options['limit'];
        }

        $result = $this->client->scan($params);

        $results = [];
        foreach ($result['Items'] as $item) {
            $results[] = $this->unmarshalItems($item);
        }

        return $results;
    }

    /**
     * Search with pagination (using LastEvaluatedKey).
     *
     * @param array $conditions
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchPaginated(array $conditions, int $page = 1, int $perPage = 20): array
    {
        $query = $this->buildQuery($conditions);

        $params = [
            'TableName' => $this->tableName,
            'FilterExpression' => $query['filter'],
            'ExpressionAttributeValues' => $query['values'],
            'ExpressionAttributeNames' => $query['names'],
            'Limit' => $perPage * $page,
        ];

        $result = $this->client->scan($params);

        $items = [];
        $lastKey = null;

        if (isset($result['LastEvaluatedKey'])) {
            $lastKey = $result['LastEvaluatedKey'];
        }

        foreach ($result['Items'] as $item) {
            $items[] = $this->unmarshalItems($item);
        }

        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);

        return [
            'data' => $paginatedItems,
            'total' => count($items),
            'page' => $page,
            'perPage' => $perPage,
            'lastKey' => $lastKey,
        ];
    }

    private function buildQuery(array $conditions): array
    {
        $filter = [];
        $values = [];
        $names = [];
        $i = 1;

        foreach ($conditions as $field => $condition) {
            if (in_array($field, ['sort', 'limit', 'offset'])) {
                continue;
            }

            $attrName = '#attr' . $i;
            $attrValue = ':val' . $i;
            $names[$attrName] = $field;

            if (is_array($condition) && isset($condition['operator'])) {
                $operator = $condition['operator'];
                $value = $condition['value'];
                $values[$attrValue] = $this->marshalValue($value);

                $filter[] = match ($operator) {
                    'eq' => "{$attrName} = {$attrValue}",
                    'ne' => "{$attrName} <> {$attrValue}",
                    'gt' => "{$attrName} > {$attrValue}",
                    'gte' => "{$attrName} >= {$attrValue}",
                    'lt' => "{$attrName} < {$attrValue}",
                    'lte' => "{$attrName} <= {$attrValue}",
                    'in' => "{$attrName} IN {$attrValue}",
                    'contains' => "contains({$attrName}, {$attrValue})",
                    'begins_with' => "begins_with({$attrName}, {$attrValue})",
                    default => "{$attrName} = {$attrValue}",
                };
            } else {
                $values[$attrValue] = $this->marshalValue($condition);
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

    private function marshalValue($value)
    {
        if (is_string($value)) {
            return ['S' => $value];
        } elseif (is_int($value)) {
            return ['N' => (string) $value];
        } elseif (is_bool($value)) {
            return ['BOOL' => $value];
        } elseif (is_array($value)) {
            return ['L' => array_map(fn($v) => $this->marshalValue($v), $value)];
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
            return (int) $value['N'];
        } elseif (isset($value['BOOL'])) {
            return $value['BOOL'];
        } elseif (isset($value['L'])) {
            return array_map(fn($v) => $this->unmarshalValue($v), $value['L']);
        }
        return null;
    }
}
