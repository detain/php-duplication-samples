<?php
declare(strict_types=1);

namespace App\Database\Transaction\Examples;

use PDO;

final class TransactionExamples
{
    public function pdoTransaction(PDO $pdo): bool
    {
        $pdo->beginTransaction();
        try {
            $pdo->exec("INSERT INTO orders (customer_id, total) VALUES (1, 100)");
            $pdo->exec("INSERT INTO order_items (order_id, product_id) VALUES (1, 1)");
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function mysqliTransaction(mysqli $mysqli): bool
    {
        $mysqli->begin_transaction();
        try {
            $mysqli->query("INSERT INTO orders (customer_id, total) VALUES (1, 100)");
            $mysqli->query("INSERT INTO order_items (order_id, product_id) VALUES (1, 1)");
            $mysqli->commit();
            return true;
        } catch (\Exception $e) {
            $mysqli->rollback();
            return false;
        }
    }

    public function doctrineTransaction(Connection $conn): bool
    {
        $conn->beginTransaction();
        try {
            $conn->insert('orders', ['customer_id' => 1, 'total' => 100]);
            $conn->insert('order_items', ['order_id' => 1, 'product_id' => 1]);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function eloquentTransaction(callable $callback): mixed
    {
        return \Illuminate\Support\Facades\DB::transaction($callback);
    }
}
