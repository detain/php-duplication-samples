<?php

declare(strict_types=1);

namespace Acme\Database\Mysql;

final class MysqlQueryBuilder
{
    public function select(string $table, array $columns = ['*']): self
    {
        $this->sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;
        $this->params = [];
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->sql .= ' WHERE ' . $column . ' ' . $operator . ' ?';
        $this->params[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->sql .= ' ORDER BY ' . $column . ' ' . strtoupper($direction);
        return $this;
    }

    public function limit(int $count, int $offset = 0): self
    {
        $this->sql .= ' LIMIT ' . $offset . ', ' . $count;
        return $this;
    }

    public function build(): array
    {
        return ['sql' => $this->sql, 'params' => $this->params];
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    private string $sql = '';
    private array $params = [];

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
