<?php

declare(strict_types=1);

namespace Acme\Seed\UserRepository;

final class UserRepositorySeed
{
    // <<<PAYLOAD:user_repository>>>
    public function findById(int $id): ?array
    {
        $row = $this->fetchRow('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$row) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->fetchRow('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$row) {
            return null;
        }
        return $this->hydrate($row);
    }

    public function insert(array $data): int
    {
        $keys = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $columns = implode(', ', $keys);
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

    protected function fetchRow(string $sql, array $params): ?array
    {
        $result = $this->db->query($sql, $params);
        if (!$result || $result->num_rows === 0) {
            return null;
        }
        return $result->fetch_assoc();
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
    // <<<END-PAYLOAD>>>
}
