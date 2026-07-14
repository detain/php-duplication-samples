<?php

declare(strict_types=1);

namespace Acme\User\Table;

use RuntimeException;

final class TableRepository
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('users', ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('users', ['email' => $email]);
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO users ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
        $values = array_values($data);
        $values[] = $id;
        $sql = "UPDATE users SET {$sets} WHERE id = ?";
        $affected = $this->execute($sql, $values);
        return $affected > 0;
    }

    protected function fetchOne(string $table, array $conditions): ?array
    {
        $where = [];
        $params = [];
        foreach ($conditions as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$table} WHERE {$whereClause} LIMIT 1";
        $result = $this->db->query($sql, $params);
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        return $this->hydrate($result->fetch_assoc());
    }

    protected function execute(string $sql, array $params): int
    {
        $this->db->query($sql, $params);
        return $this->db->affected_rows ?? 0;
    }

    protected function lastInsertId(): int
    {
        $result = $this->db->query('SELECT LAST_INSERT_ID()');
        $row = $result->fetch_row();
        return (int) ($row[0] ?? 0);
    }

    protected function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['created_at'] = strtotime($row['created_at'] ?? 'now');
        return $row;
    }

    private $db;

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
