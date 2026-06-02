<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use RuntimeException;

final class DbalOrderRepository
{
    private Connection $connection;

    public function __construct(array $params)
    {
        try {
            $this->connection = DriverManager::getConnection([
                'dbname' => $params['database'] ?? '',
                'user' => $params['username'] ?? '',
                'password' => $params['password'] ?? '',
                'host' => $params['host'] ?? 'localhost',
                'driver' => 'pdo_mysql',
                'charset' => 'utf8mb4',
            ]);
        } catch (DbalException $e) {
            throw new RuntimeException('DBAL connection failed: ' . $e->getMessage(), 0, $e);
        }
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

        $sql = 'INSERT INTO orders (customer_id, total_amount, status, notes, created_at)
                VALUES (:customer_id, :total_amount, :status, :notes, NOW())';

        try {
            $this->connection->executeStatement($sql, [
                'customer_id' => $customerId,
                'total_amount' => $totalAmount,
                'status' => $status,
                'notes' => $notes,
            ]);

            $insertId = $this->connection->lastInsertId();

            if ($insertId === false || $insertId === '0') {
                throw new RuntimeException('Failed to retrieve last insert ID');
            }

            return (int) $insertId;
        } catch (DbalException $e) {
            throw new RuntimeException('DBAL insert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function insertAndSync(array $orderData): array
    {
        $orderId = $this->insert($orderData);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM orders WHERE id = :id',
            ['id' => $orderId]
        );

        return $result;
    }
}
