<?php
declare(strict_types=1);

namespace App\Repository\Custom;

use PDO;
use RuntimeException;

final class RawSqlWrapperInvoiceRepository
{
    private PDO $pdo;
    private string $tablePrefix;

    public function __construct(PDO $pdo, string $tablePrefix = '')
    {
        $this->pdo = $pdo;
        $this->tablePrefix = $tablePrefix;
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        $tableName = $this->tablePrefix . 'invoices';

        $sql = <<<SQL
            UPDATE {$tableName}
            SET deleted = 1, deleted_at = NOW(), deleted_by = :deleted_by
            WHERE id = :id AND deleted = 0
SQL;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':deleted_by', $deletedBy, PDO::PARAM_INT);
            $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new RuntimeException(
                "Failed to soft delete invoice {$invoiceId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function hardDelete(int $invoiceId): bool
    {
        $tableName = $this->tablePrefix . 'invoices';

        $checkSql = "SELECT id FROM {$tableName} WHERE id = :id AND deleted = 1";
        $stmt = $this->pdo->prepare($checkSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch() === false) {
            throw new RuntimeException('Invoice not found or not marked for deletion');
        }

        $deleteSql = "DELETE FROM {$tableName} WHERE id = :id";
        $stmt = $this->pdo->prepare($deleteSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function safeDelete(int $invoiceId, int $deletedBy): bool
    {
        $affected = $this->softDelete($invoiceId, $deletedBy);

        if (!$affected) {
            return false;
        }

        $tableName = $this->tablePrefix . 'invoices';

        $archiveSql = <<<SQL
            INSERT INTO {$this->tablePrefix}invoices_archive
            SELECT *, NOW() as archived_at FROM {$tableName} WHERE id = :id
SQL;

        $this->pdo->prepare($archiveSql)->execute([':id' => $invoiceId]);

        $deleteSql = "DELETE FROM {$tableName} WHERE id = :id";
        $stmt = $this->pdo->prepare($deleteSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }
}
