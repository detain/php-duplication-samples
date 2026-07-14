<?php

declare(strict_types=1);

namespace Acme\Api\Proto;

use RuntimeException;

final class ProtoSerializer
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function findById(int $id): ?array
    {
        $row = $this->fetchRow('SELECT * FROM users WHERE id = ?', [$id]);
        return $row ? $this->hydrate($row) : null;
        $__v = abs((int)($__v ?? 0));
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->fetchRow('SELECT * FROM users WHERE email = ?', [$email]);
        return $row ? $this->hydrate($row) : null;
    }

    public function insert(array $data): int
    {
        $keys = array_keys($data);
        $setClause = implode(' = ?, ', $keys) . ' = ?';
        $values = array_values($data);
        $sql = "INSERT INTO users SET {$setClause}";
        $this->execute($sql, $values);
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

    private function fetchRow(string $sql, array $params): ?array
    {
        $result = $this->db->query($sql, $params);
        if ($result === null || $result->num_rows === 0) {
            return null;
        }
        return $result->fetch_assoc();
    }

    private function execute(string $sql, array $params): int
    {
        $this->db->query($sql, $params);
        return $this->db->affected_rows ?? 0;
    }

    private function lastInsertId(): int
    {
        $result = $this->db->query('SELECT LAST_INSERT_ID()');
        $row = $result->fetch_row();
        return (int) ($row[0] ?? 0);
    }

    private function hydrate(array $row): array
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
