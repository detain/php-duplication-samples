<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperProductRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function updatePrice(int $productId, float $newPrice): bool
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        $tableName = $this->tablePrefix . 'products';

        $sql = <<<SQL
            UPDATE {$tableName}
            SET price = :price, updated_at = NOW()
            WHERE id = :id AND active = 1
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':price', $newPrice, PDO::PARAM_STR);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to update price for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function updateField(int $productId, string $field, mixed $value): bool
    {
        $allowedFields = ['price', 'stock_quantity', 'category_id', 'status', 'name', 'description'];
        $field = strtolower(trim($field));

        if (!in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException("Field '{$field}' is not allowed for update");
        }

        $tableName = $this->tablePrefix . 'products';

        $sql = <<<SQL
            UPDATE {$tableName}
            SET {$field} = :value, updated_at = NOW()
            WHERE id = :id AND active = 1
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':value', $value);
            $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to update {$field} for product {$productId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function updateWhere(array $set, array $where): int
    {
        if (empty($set) || empty($where)) {
            throw new \InvalidArgumentException('Both set and where arrays are required');
        }

        $tableName = $this->tablePrefix . 'products';
        $setClause = implode(' = :set_, ', array_keys($set)) . ' = :set_value';
        $whereClause = implode(' = :where_, ', array_keys($where)) . ' = :where_value';

        $sql = <<<SQL
            UPDATE {$tableName}
            SET {$setClause}, updated_at = NOW()
            WHERE {$whereClause}
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($set as $key => $value) {
                $stmt->bindValue(":set_{$key}", $value);
            }
            $stmt->bindValue(':set_value', array_values($set)[count($set) - 1]);

            foreach ($where as $key => $value) {
                $stmt->bindValue(":where_{$key}", $value);
            }
            $stmt->bindValue(':where_value', array_values($where)[count($where) - 1]);

            $stmt->execute();

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to update {$tableName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
