<?php
declare(strict_types=1);

namespace App\Repository\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoPrepareOrderRepository
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
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':total_amount', $totalAmount, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();

            $insertId = $this->pdo->lastInsertId();

            if ($insertId === false || $insertId === '0') {
                throw new RuntimeException('Failed to retrieve last insert ID');
            }

            return (int) $insertId;
        } catch (PDOException $e) {
            throw new RuntimeException('Insert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function insertWithTransaction(array $orderData, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            $orderId = $this->insert($orderData);

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                 VALUES (:order_id, :product_id, :quantity, :unit_price)'
            );

            foreach ($items as $item) {
                $itemStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $itemStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
                $itemStmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
                $itemStmt->bindValue(':unit_price', $item['unit_price'], PDO::PARAM_STR);
                $itemStmt->execute();
            }

            $this->pdo->commit();

            return $orderId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
