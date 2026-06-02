<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralOrderRepository
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

    public function insert(array $orderData): int
    {
        $customerId = (int)($orderData['customer_id'] ?? 0);
        $totalAmount = (float)($orderData['total_amount'] ?? 0.0);
        $status = $this->escape($orderData['status'] ?? 'pending');
        $notes = $this->escape($orderData['notes'] ?? '');

        if ($customerId <= 0) {
            throw new \InvalidArgumentException('Valid customer_id is required');
        }

        $sql = "INSERT INTO orders (customer_id, total_amount, status, notes, created_at)
                VALUES ({$customerId}, {$totalAmount}, '{$status}', '{$notes}', NOW())";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Insert failed: ' . mysqli_error($this->connection));
        }

        $insertId = mysqli_insert_id($this->connection);

        if ($insertId === 0 || $insertId === false) {
            throw new RuntimeException('Failed to retrieve last insert ID');
        }

        return (int) $insertId;
    }

    private function escape(string $value): string
    {
        return mysqli_real_escape_string($this->connection, $value);
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
