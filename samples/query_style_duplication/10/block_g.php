<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperUpsertRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function upsertProduct(array $productData): int
    {
        $tableName = $this->tablePrefix . 'products';

        $sql = <<<SQL
            INSERT INTO {$tableName} (sku, name, price, stock_quantity, status, created_at, updated_at)
            VALUES (:sku, :name, :price, :stock, :status, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                price = VALUES(price),
                stock_quantity = VALUES(stock_quantity),
                status = VALUES(status),
                updated_at = NOW()
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':sku', $productData['sku']);
            $stmt->bindValue(':name', $productData['name']);
            $stmt->bindValue(':price', $productData['price'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(':stock', $productData['stock_quantity'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':status', $productData['status'] ?? 'active');
            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Product upsert failed on {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function upsertUser(array $userData): int
    {
        $tableName = $this->tablePrefix . 'users';

        $sql = <<<SQL
            INSERT INTO {$tableName} (email, username, first_name, last_name, status, created_at, updated_at)
            VALUES (:email, :username, :first_name, :last_name, :status, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                status = VALUES(status),
                updated_at = NOW()
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':email', $userData['email']);
            $stmt->bindValue(':username', $userData['username']);
            $stmt->bindValue(':first_name', $userData['first_name'] ?? '');
            $stmt->bindValue(':last_name', $userData['last_name'] ?? '');
            $stmt->bindValue(':status', $userData['status'] ?? 'active');
            $stmt->execute();

            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "User upsert failed on {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function upsertCounter(string $counterName, int $delta = 1): int
    {
        $tableName = $this->tablePrefix . 'counters';

        $sql = <<<SQL
            INSERT INTO {$tableName} (name, value, updated_at)
            VALUES (:name, :value, NOW())
            ON DUPLICATE KEY UPDATE
                value = value + :delta,
                updated_at = NOW()
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':name', $counterName);
            $stmt->bindValue(':value', max(0, $delta), PDO::PARAM_INT);
            $stmt->bindValue(':delta', $delta, PDO::PARAM_INT);
            $stmt->execute();

            $selectSql = "SELECT value FROM {$tableName} WHERE name = :name";
            $selectStmt = $this->pdo->prepare($selectSql);
            $selectStmt->bindValue(':name', $counterName);
            $selectStmt->execute();

            $result = $selectStmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['value'] ?? 0);
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Counter upsert failed on {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function upsertMetadata(string $entityType, int $entityId, array $metadata): bool
    {
        $tableName = $this->tablePrefix . 'metadata';

        $sql = <<<SQL
            INSERT INTO {$tableName} (entity_type, entity_id, meta_key, meta_value, updated_at)
            VALUES (:entity_type, :entity_id, :meta_key, :meta_value, NOW())
            ON DUPLICATE KEY UPDATE
                meta_value = VALUES(meta_value),
                updated_at = NOW()
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($metadata as $key => $value) {
                $stmt->bindValue(':entity_type', $entityType);
                $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
                $stmt->bindValue(':meta_key', $key);
                $stmt->bindValue(':meta_value', is_array($value) ? json_encode($value) : (string)$value);
                $stmt->execute();
            }

            return true;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Metadata upsert failed on {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
