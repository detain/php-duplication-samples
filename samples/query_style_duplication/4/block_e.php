<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentInvoiceRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        try {
            $affected = $this->db::table('invoices')
                ->where('id', $invoiceId)
                ->where('deleted', 0)
                ->update([
                    'deleted' => 1,
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'deleted_by' => $deletedBy,
                ]);

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent soft delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hardDelete(int $invoiceId): bool
    {
        try {
            $exists = $this->db::table('invoices')
                ->where('id', $invoiceId)
                ->where('deleted', 1)
                ->exists();

            if (!$exists) {
                throw new RuntimeException('Invoice not found or not marked for deletion');
            }

            $affected = $this->db::table('invoices')
                ->where('id', $invoiceId)
                ->delete();

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent hard delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteWithTransaction(int $invoiceId): bool
    {
        return $this->db::getDatabaseManager()->transaction(function () use ($invoiceId) {
            $this->db::table('invoice_items')
                ->where('invoice_id', $invoiceId)
                ->delete();

            return $this->db::table('invoices')
                ->where('id', $invoiceId)
                ->delete() > 0;
        });
    }

    public function purgeDeleted(\DateTimeInterface $before): int
    {
        try {
            return $this->db::table('invoices')
                ->where('deleted', 1)
                ->where('deleted_at', '<', $before->format('Y-m-d H:i:s'))
                ->delete();
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent purge failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
