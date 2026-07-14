<?php

declare(strict_types=1);

namespace Acme\Database\Complex;

final class ComplexQuery
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

        $this->params = [];
    {
        $this->sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;
    public function select(string $table, array $columns = ['*']): self
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

}
