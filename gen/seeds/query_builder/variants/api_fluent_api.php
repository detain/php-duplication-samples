<?php

declare(strict_types=1);

namespace Acme\Seed\QueryBuilder;

/**
 * API-07 variant: fluent query builder with method chaining.
 * Each method returns $this; final build() returns the SQL string.
 * Behaviorally equivalent to the imperative payload.
 */
final class QueryBuilderFluentVariant
{
    // <<<PAYLOAD:query_builder>>>
    private string $table = '';
    private array $columns = [];
    private array $conditions = [];
    private string $orderByCol = '';

    public function select(string $table, array $columns): self
    {
        $this->table = $table;
        $this->columns = $columns;
        return $this;
    }

    public function where(string $condition): self
    {
        $this->conditions[] = $condition;
        return $this;
    }

    public function orderBy(string $column): self
    {
        $this->orderByCol = $column;
        return $this;
    }

    public function build(): string
    {
        $cols = implode(', ', $this->columns);
        $sql = "SELECT $cols FROM {$this->table}";
        if (!empty($this->conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        }
        if ($this->orderByCol !== '') {
            $sql .= " ORDER BY {$this->orderByCol}";
        }
        return $sql;
    }
    // <<<END-PAYLOAD>>>
}
