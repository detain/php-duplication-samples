<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class OrderDetailRepository implements OrderDetailRepositoryInterface
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(PDO $pdo, string $prefix = '')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        $sql = "SELECT o.id, o.order_number, o.customer_id, o.total_amount, o.status,
                       o.created_at, o.shipping_address,
                       c.first_name, c.last_name, c.email,
                       p.name as payment_method, p.provider_reference
                FROM {$this->prefix}orders o
                INNER JOIN {$this->prefix}customers c ON o.customer_id = c.id
                LEFT JOIN {$this->prefix}payments p ON o.id = p.order_id
                WHERE o.id = :order_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return null;
            }

            $itemsSql = "SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price,
                                pr.name, pr.sku, pr.image_url
                         FROM {$this->prefix}order_items oi
                         INNER JOIN {$this->prefix}products pr ON oi.product_id = pr.id
                         WHERE oi.order_id = :order_id";

            $itemsStmt = $this->pdo->prepare($itemsSql);
            $itemsStmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $itemsStmt->execute();

            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            return $order;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to get order details: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getCustomerOrderHistory(int $customerId, int $limit = 20): array
    {
        $sql = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                       c.first_name, c.last_name,
                       COUNT(oi.id) as item_count
                FROM {$this->prefix}orders o
                INNER JOIN {$this->prefix}customers c ON o.customer_id = c.id
                LEFT JOIN {$this->prefix}order_items oi ON o.id = oi.order_id
                WHERE o.customer_id = :customer_id
                GROUP BY o.id, o.order_number, o.total_amount, o.status, o.created_at,
                         c.first_name, c.last_name
                ORDER BY o.created_at DESC
                LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to get customer order history: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getOrdersWithPagination(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM {$this->prefix}orders";
        $stmt = $this->pdo->query($countSql);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $sql = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                       c.first_name, c.last_name, c.email
                FROM {$this->prefix}orders o
                INNER JOIN {$this->prefix}customers c ON o.customer_id = c.id
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to get paginated orders: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
