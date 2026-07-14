<?php

declare(strict_types=1);

namespace Acme\Database\Dead;

use RuntimeException;

final class DeadQuery
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function select(string $table, array $columns = ['*']): self
    {
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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
