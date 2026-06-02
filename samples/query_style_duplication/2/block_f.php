<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderOrderRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(array $orderData): int
    {
        $customerId = (int)($orderData['customer_id'] ?? 0);
        $totalAmount = (float)($orderData['total_amount'] ?? 0.0);
        $status = $orderData['status'] ?? 'pending';
        $notes = $orderData['notes'] ?? '';

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Valid customer_id is required');
        }

        try {
            $this->connection->insert('orders', [
                'customer_id' => $customerId,
                'total_amount' => $totalAmount,
                'status' => $status,
                'notes' => $notes,
                'created_at' => new \DateTimeImmutable(),
            ]);

            $insertId = $this->connection->lastInsertId();

            if ($insertId === false || $insertId === '0') {
                throw new RuntimeException('Failed to retrieve last insert ID');
            }

            return (int) $insertId;
        } catch (\Exception $e) {
            throw new RuntimeException('Symfony QueryBuilder insert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function insertAndFetch(array $orderData): array
    {
        $orderId = $this->insert($orderData);

        $qb = $this->connection->createQueryBuilder();
        $result = $qb->select('*')
            ->from('orders', 'o')
            ->where('o.id = :id')
            ->setParameter('id', $orderId)
            ->execute()
            ->fetchAssociative();

        return $result ?: [];
    }
}
