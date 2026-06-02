<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use mysqli_stmt;
use RuntimeException;

final class MysqliOopOrderRepository
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

    public function insert(array $orderData): int
    {
        $customerId = (int)($orderData['customer_id'] ?? 0);
        $totalAmount = (float)($orderData['total_amount'] ?? 0.0);
        $status = $orderData['status'] ?? 'pending';
        $notes = $orderData['notes'] ?? '';

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Valid customer_id is required');
        }

        $sql = "INSERT INTO orders (customer_id, total_amount, status, notes, created_at)
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('idss', $customerId, $totalAmount, $status, $notes);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        if ($insertId === 0) {
            throw new RuntimeException('Failed to retrieve last insert ID');
        }

        return (int) $insertId;
    }

    public function insertBatch(array $orders): array
    {
        $insertIds = [];
        $this->connection->begin_transaction();

        try {
            foreach ($orders as $orderData) {
                $insertIds[] = $this->insert($orderData);
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $insertIds;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
