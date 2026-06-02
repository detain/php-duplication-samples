<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalInvoiceRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        try {
            $affected = $this->connection->update(
                'invoices',
                [
                    'deleted' => 1,
                    'deleted_at' => new \DateTimeImmutable(),
                    'deleted_by' => $deletedBy,
                ],
                [
                    'id' => $invoiceId,
                    'deleted' => 0,
                ]
            );

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL soft delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hardDelete(int $invoiceId): bool
    {
        try {
            $exists = $this->connection->fetchAssociative(
                'SELECT id FROM invoices WHERE id = ? AND deleted = 1',
                [$invoiceId]
            );

            if (!$exists) {
                throw new RuntimeException('Invoice not found or not marked for deletion');
            }

            $affected = $this->connection->delete('invoices', ['id' => $invoiceId]);

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL hard delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteWithItems(int $invoiceId): bool
    {
        $this->connection->beginTransaction();

        try {
            $this->connection->delete('invoice_items', ['invoice_id' => $invoiceId]);
            $affected = $this->connection->delete('invoices', ['id' => $invoiceId]);

            $this->connection->commit();

            return $affected > 0;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function softDeleteBatch(array $invoiceIds, int $deletedBy): int
    {
        if (empty($invoiceIds)) {
            return 0;
        }

        try {
            $affected = 0;
            $now = new \DateTimeImmutable();

            foreach ($invoiceIds as $invoiceId) {
                $result = $this->connection->update(
                    'invoices',
                    [
                        'deleted' => 1,
                        'deleted_at' => $now,
                        'deleted_by' => $deletedBy,
                    ],
                    [
                        'id' => $invoiceId,
                        'deleted' => 0,
                    ]
                );
                $affected += $result;
            }

            return $affected;
        } catch (\Exception $e) {
            throw new RuntimeException('DBAL batch soft delete failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
