<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class InvoiceRepository implements InvoiceRepositoryInterface
{
    private PDO $pdo;
    private string $tableName;

    public function __construct(PDO $pdo, string $tableName = 'invoices')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        if ($invoiceId <= 0) {
            throw new \InvalidArgumentException('Invoice ID must be positive');
        }

        $sql = "UPDATE {$this->tableName}
                SET deleted = 1, deleted_at = NOW(), deleted_by = :deleted_by
                WHERE id = :id AND deleted = 0";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':deleted_by', $deletedBy, PDO::PARAM_INT);
            $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to soft delete invoice {$invoiceId}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function hardDelete(int $invoiceId): bool
    {
        if ($invoiceId <= 0) {
            throw new \InvalidArgumentException('Invoice ID must be positive');
        }

        $checkSql = "SELECT id FROM {$this->tableName} WHERE id = :id AND deleted = 1";
        $stmt = $this->pdo->prepare($checkSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch() === false) {
            throw new RuntimeException('Invoice not found or not marked for deletion');
        }

        $deleteSql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->pdo->prepare($deleteSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function deleteWithItems(int $invoiceId): bool
    {
        if ($invoiceId <= 0) {
            throw new \InvalidArgumentException('Invoice ID must be positive');
        }

        $this->pdo->beginTransaction();

        try {
            $itemStmt = $this->pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = :id");
            $itemStmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
            $itemStmt->execute();

            $invoiceStmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE id = :id");
            $invoiceStmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
            $invoiceStmt->execute();

            $this->pdo->commit();

            return $invoiceStmt->rowCount() > 0;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
