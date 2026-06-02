<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareInvoiceRepository
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        $sql = 'UPDATE invoices
                SET deleted = 1, deleted_at = NOW(), deleted_by = :deleted_by
                WHERE id = :id AND deleted = 0';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':deleted_by', $deletedBy, PDO::PARAM_INT);
            $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('Soft delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hardDelete(int $invoiceId): bool
    {
        $checkSql = 'SELECT id FROM invoices WHERE id = :id AND deleted = 1';
        $stmt = $this->pdo->prepare($checkSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch() === false) {
            throw new RuntimeException('Invoice not found or not marked for deletion');
        }

        $deleteSql = 'DELETE FROM invoices WHERE id = :id';
        $stmt = $this->pdo->prepare($deleteSql);
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function deleteWithTransaction(int $invoiceId): bool
    {
        $this->pdo->beginTransaction();

        try {
            $itemStmt = $this->pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = :invoice_id');
            $itemStmt->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $itemStmt->execute();

            $invoiceStmt = $this->pdo->prepare('DELETE FROM invoices WHERE id = :id');
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

    public function purgeOldInvoices(\DateTimeInterface $beforeDate): int
    {
        $sql = 'DELETE FROM invoices WHERE deleted = 1 AND deleted_at < :before_date';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':before_date', $beforeDate->format('Y-m-d H:i:s'));
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException('Purge failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
