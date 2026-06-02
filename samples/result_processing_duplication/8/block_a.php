<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;

final class ResultAggregation
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function aggregateWithFetchAssoc(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                status,
                COUNT(*) as count,
                SUM(total) as total,
                AVG(total) as average,
                MIN(created_at) as first_date,
                MAX(created_at) as last_date
            FROM orders
            GROUP BY status
        ');

        $aggregates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aggregates[$row['status']] = $row;
        }

        return $aggregates;
    }

    public function aggregateWithFetchAll(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                category,
                COUNT(*) as product_count,
                SUM(stock) as total_stock,
                AVG(price) as avg_price
            FROM products
            GROUP BY category
        ');

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

        return $result;
    }

    public function aggregateWithIterator(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(amount) as daily_revenue
            FROM orders
            GROUP BY DATE(created_at)
        ');

        $dailyStats = [];
        foreach ($stmt as $row) {
            $dailyStats[$row['date']] = $row;
        }

        return $dailyStats;
    }

    public function aggregateWithKeyedResult(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                user_id,
                COUNT(*) as order_count,
                SUM(total) as lifetime_value
            FROM orders
            GROUP BY user_id
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
    }
}
