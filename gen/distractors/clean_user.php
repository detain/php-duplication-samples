<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function findById(int $id): ?array
    {
        $result = $this->db->query("SELECT * FROM items WHERE id = ?", [$id]);
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM items";
        if (!empty($filters)) {
            $sql .= " WHERE " . implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($filters)));
        }
        return $this->db->query($sql, array_values($filters))->fetch_all(MYSQLI_ASSOC);
    }
}
