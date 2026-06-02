<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperUserRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function findById(int $id): ?array
    {
        $tableName = $this->tablePrefix . 'users';

        $sql = <<<SQL
            SELECT id, username, email, first_name, last_name, created_at, status
            FROM {$tableName}
            WHERE id = :id
              AND active = 1
            LIMIT 1
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to fetch user with ID {$id} from {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function findByField(string $field, string $value): ?array
    {
        $tableName = $this->tablePrefix . 'users';
        $allowedFields = ['username', 'email', 'id'];
        $field = strtolower(trim($field));

        if (!in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException("Field '{$field}' is not allowed for lookup");
        }

        $sql = <<<SQL
            SELECT id, username, email, first_name, last_name, created_at, status
            FROM {$tableName}
            WHERE {$field} = :value
              AND active = 1
            LIMIT 1
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':value', $value);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to fetch user by {$field}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
