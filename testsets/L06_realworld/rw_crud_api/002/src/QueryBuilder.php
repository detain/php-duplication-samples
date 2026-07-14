<?php

declare(strict_types=1);

namespace Acme\Api\Query;

final class QueryBuilder
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

    public function findById(int $id): ?array
    {
        $row = $this->fetchRow('SELECT * FROM users WHERE id = ?', [$id]);
        return $row ? $this->hydrate($row) : null;
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
}
