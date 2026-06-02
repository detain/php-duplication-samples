<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralInvoiceRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = mysqli_connect($host, $username, $password, $database);
        if ($this->connection === false) {
            throw new RuntimeException('Connection failed: ' . mysqli_connect_error());
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        $invoiceId = mysqli_real_escape_string($this->connection, (string)$invoiceId);
        $deletedBy = mysqli_real_escape_string($this->connection, (string)$deletedBy);
        $deletedAt = date('Y-m-d H:i:s');

        $sql = "UPDATE invoices
                SET deleted = 1, deleted_at = '{$deletedAt}', deleted_by = {$deletedBy}
                WHERE id = {$invoiceId}
                  AND deleted = 0";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Soft delete failed: ' . mysqli_error($this->connection));
        }

        return mysqli_affected_rows($this->connection) > 0;
    }

    public function hardDelete(int $invoiceId): bool
    {
        $invoiceId = mysqli_real_escape_string($this->connection, (string)$invoiceId);

        $checkSql = "SELECT id FROM invoices WHERE id = {$invoiceId} AND deleted = 1";
        $checkResult = mysqli_query($this->connection, $checkSql);

        if ($checkResult === false || mysqli_num_rows($checkResult) === 0) {
            throw new RuntimeException('Invoice not found or not marked for deletion');
        }
        mysqli_free_result($checkResult);

        $deleteSql = "DELETE FROM invoices WHERE id = {$invoiceId}";
        $result = mysqli_query($this->connection, $deleteSql);

        if ($result === false) {
            throw new RuntimeException('Hard delete failed: ' . mysqli_error($this->connection));
        }

        return mysqli_affected_rows($this->connection) > 0;
    }

    public function deleteWithItems(int $invoiceId): bool
    {
        $invoiceId = (int)$invoiceId;

        $this->connection->begin_transaction();

        try {
            mysqli_query($this->connection, "DELETE FROM invoice_items WHERE invoice_id = {$invoiceId}");

            $sql = "DELETE FROM invoices WHERE id = {$invoiceId}";
            $result = mysqli_query($this->connection, $sql);

            if ($result === false) {
                throw new RuntimeException('Delete failed: ' . mysqli_error($this->connection));
            }

            $this->connection->commit();
            return mysqli_affected_rows($this->connection) > 0;
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
