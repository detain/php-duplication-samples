<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopInvoiceRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);
        if ($this->connection->connect_error) {
            throw new RuntimeException('Connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE invoices
             SET deleted = 1, deleted_at = ?, deleted_by = ?
             WHERE id = ? AND deleted = 0'
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $deletedAt = date('Y-m-d H:i:s');
        $stmt->bind_param('sii', $deletedAt, $deletedBy, $invoiceId);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }

    public function hardDelete(int $invoiceId): bool
    {
        $checkStmt = $this->connection->prepare(
            'SELECT id FROM invoices WHERE id = ? AND deleted = 1'
        );

        if ($checkStmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $checkStmt->bind_param('i', $invoiceId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            $checkStmt->close();
            throw new RuntimeException('Invoice not found or not marked for deletion');
        }
        $checkStmt->close();

        $deleteStmt = $this->connection->prepare('DELETE FROM invoices WHERE id = ?');

        if ($deleteStmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $deleteStmt->bind_param('i', $invoiceId);

        if (!$deleteStmt->execute()) {
            $deleteStmt->close();
            throw new RuntimeException('Execute failed: ' . $deleteStmt->error);
        }

        $affected = $deleteStmt->affected_rows;
        $deleteStmt->close();

        return $affected > 0;
    }

    public function deleteBatch(array $invoiceIds): int
    {
        if (empty($invoiceIds)) {
            return 0;
        }

        $ids = implode(',', array_map('intval', $invoiceIds));

        $this->connection->begin_transaction();

        try {
            mysqli_query($this->connection, "DELETE FROM invoice_items WHERE invoice_id IN ({$ids})");

            $result = mysqli_query($this->connection, "DELETE FROM invoices WHERE id IN ({$ids})");

            if ($result === false) {
                throw new RuntimeException('Batch delete failed');
            }

            $this->connection->commit();

            return mysqli_affected_rows($this->connection);
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
