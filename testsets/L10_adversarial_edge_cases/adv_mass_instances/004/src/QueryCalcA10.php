<?php

declare(strict_types=1);

namespace Acme\Query\CalcA;

final class QueryCalcA10
{
    /**
     * Build a SQL SELECT query step by step.
     * Payload: imperative API — methods return void, state held in fields.
     */
    private string $table = '';
    private array $columns = [];
    private array $conditions = [];
    private string $orderBy = '';

    public function select(string $table, array $columns): void
    {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function where(string $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function orderBy(string $column): void
    {
        $this->orderBy = $column;
    }

    public function build(): string
    {
        $cols = implode(', ', $this->columns);
        $sql = "SELECT $cols FROM {$this->table}";
        if (!empty($this->conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        }
        if ($this->orderBy !== '') {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        return $sql;
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
