<?php
declare(strict_types=1);

namespace App\Database\NoSQL;

/**
 * Document repository using ArangoDB.
 * Demonstrates "Search with WHERE clause" in ArangoDB style.
 */
final class ArangoDBSearchRepository
{
    private \ArangoDB\Client\Client $client;
    private string $collection;

    public function __construct(\ArangoDB\Client\Client $client, string $collection = 'documents')
    {
        $this->client = $client;
        $this->collection = $collection;
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
        $collection = $this->client->collection($this->collection);

        $aql = $this->buildAql($conditions, $options);

        $cursor = $this->client->query($aql['query'], $aql['bindVars']);

        $results = [];
        foreach ($cursor as $document) {
            $results[] = $document;
        }

        return $results;
    }

    /**
     * Search with pagination.
     *
     * @param array $conditions
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchPaginated(array $conditions, int $page = 1, int $perPage = 20): array
    {
        $collection = $this->client->collection($this->collection);

        $countAql = $this->buildAql($conditions, [], true);
        $countCursor = $this->client->query($countAql['query'], $countAql['bindVars']);
        $total = $countCursor->first()['count'] ?? 0;

        $aql = $this->buildAql($conditions, [
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);

        $cursor = $this->client->query($aql['query'], $aql['bindVars']);

        $results = [];
        foreach ($cursor as $document) {
            $results[] = $document;
        }

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    private function buildAql(array $conditions, array $options, bool $countOnly = false): array
    {
        $where = [];
        $bindVars = [];
        $i = 1;

        foreach ($conditions as $field => $condition) {
            if ($field === 'sort' || $field === 'limit' || $field === 'offset') {
                continue;
            }

            if (is_array($condition) && isset($condition['operator'])) {
                $operator = $condition['operator'];
                $value = $condition['value'];
                $bindKey = 'var' . $i++;

                $bindVars[$bindKey] = $value;

                $where[] = match ($operator) {
                    'eq' => "doc.{$field} == @{$bindKey}",
                    'ne' => "doc.{$field} != @{$bindKey}",
                    'gt' => "doc.{$field} > @{$bindKey}",
                    'gte' => "doc.{$field} >= @{$bindKey}",
                    'lt' => "doc.{$field} < @{$bindKey}",
                    'lte' => "doc.{$field} <= @{$bindKey}",
                    'in' => "doc.{$field} IN @{$bindKey}",
                    'like' => "LIKE(doc.{$field}, @{$bindKey})",
                    default => "doc.{$field} == @{$bindKey}",
                };
            } else {
                $bindKey = 'var' . $i++;
                $bindVars[$bindKey] = $condition;
                $where[] = "doc.{$field} == @{$bindKey}";
            }
        }

        $collectionName = $this->collection;
        $query = "FOR doc IN {$collectionName}";

        if (!empty($where)) {
            $query .= " FILTER " . implode(' AND ', $where);
        }

        if ($countOnly) {
            $query .= " COLLECT WITH COUNT INTO count RETURN count";
            return ['query' => $query, 'bindVars' => $bindVars];
        }

        if (isset($conditions['sort'])) {
            $sortField = array_key_first($conditions['sort']);
            $sortOrder = $conditions['sort'][$sortField] === 1 ? 'ASC' : 'DESC';
            $query .= " SORT doc.{$sortField} {$sortOrder}";
        }

        if (isset($options['limit'])) {
            $query .= " LIMIT " . (int) $options['limit'];

            if (isset($options['offset'])) {
                $query .= " OFFSET " . (int) $options['offset'];
            }
        }

        $query .= " RETURN doc";

        return ['query' => $query, 'bindVars' => $bindVars];
    }
}
