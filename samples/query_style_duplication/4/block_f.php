<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderInvoiceRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function softDelete(int $invoiceId, int $deletedBy): bool
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $affected = $qb->update('invoices', 'i')
                ->set('i.deleted', ':deleted')
                ->set('i.deleted_at', ':deleted_at')
                ->set('i.deleted_by', ':deleted_by')
                ->where('i.id = :id')
                ->andWhere('i.deleted = :not_deleted')
                ->setParameters([
                    'deleted' => 1,
                    'deleted_at' => new \DateTimeImmutable(),
                    'deleted_by' => $deletedBy,
                    'id' => $invoiceId,
                    'not_deleted' => 0,
                ])
                ->execute();

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder soft delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function hardDelete(int $invoiceId): bool
    {
        try {
            $check = $this->connection->createQueryBuilder();
            $exists = $check->select('id')
                ->from('invoices', 'i')
                ->where('i.id = :id')
                ->andWhere('i.deleted = :deleted')
                ->setParameter('id', $invoiceId)
                ->setParameter('deleted', 1)
                ->execute()
                ->fetchAssociative();

            if (!$exists) {
                throw new RuntimeException('Invoice not found or not marked for deletion');
            }

            $qb = $this->connection->createQueryBuilder();

            $affected = $qb->delete('invoices', 'i')
                ->where('i.id = :id')
                ->setParameter('id', $invoiceId)
                ->execute();

            return $affected > 0;
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder hard delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteWithItems(int $invoiceId): bool
    {
        $this->connection->beginTransaction();

        try {
            $itemQb = $this->connection->createQueryBuilder();
            $itemQb->delete('invoice_items', 'ii')
                ->where('ii.invoice_id = :invoice_id')
                ->setParameter('invoice_id', $invoiceId)
                ->execute();

            $invoiceQb = $this->connection->createQueryBuilder();
            $affected = $invoiceQb->delete('invoices', 'i')
                ->where('i.id = :id')
                ->setParameter('id', $invoiceId)
                ->execute();

            $this->connection->commit();

            return $affected > 0;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
