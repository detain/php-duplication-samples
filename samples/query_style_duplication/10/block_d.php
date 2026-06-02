<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DbalUpsertRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function upsertProduct(array $productData): int
    {
        try {
            $this->connection->insert('products', [
                'sku' => $productData['sku'],
                'name' => $productData['name'],
                'price' => (float)($productData['price'] ?? 0),
                'stock_quantity' => (int)($productData['stock_quantity'] ?? 0),
                'status' => $productData['status'] ?? 'active',
                'created_at' => new \DateTimeImmutable(),
                'updated_at' => new \DateTimeImmutable(),
            ], [
                'sku' => \Doctrine\DBAL\ParameterType::STRING,
                'name' => \Doctrine\DBAL\ParameterType::STRING,
                'price' => \Doctrine\DBAL\ParameterType::STR,
                'stock_quantity' => \Doctrine\DBAL\ParameterType::INTEGER,
                'status' => \Doctrine\DBAL\ParameterType::STRING,
                'created_at' => \Doctrine\DBAL\ParameterType::STRING,
                'updated_at' => \Doctrine\DBAL\ParameterType::STRING,
            ]);

            return (int)$this->connection->lastInsertId();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->connection->update('products', [
                    'name' => $productData['name'],
                    'price' => (float)($productData['price'] ?? 0),
                    'stock_quantity' => (int)($productData['stock_quantity'] ?? 0),
                    'status' => $productData['status'] ?? 'active',
                    'updated_at' => new \DateTimeImmutable(),
                ], [
                    'sku' => $productData['sku'],
                ]);

                $result = $this->connection->fetchAssociative(
                    'SELECT id FROM products WHERE sku = ?',
                    [$productData['sku']]
                );

                return (int)$result['id'];
            }

            throw new RuntimeException('DBAL upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertUser(array $userData): int
    {
        try {
            $this->connection->insert('users', [
                'email' => $userData['email'],
                'username' => $userData['username'],
                'first_name' => $userData['first_name'] ?? '',
                'last_name' => $userData['last_name'] ?? '',
                'status' => $userData['status'] ?? 'active',
                'created_at' => new \DateTimeImmutable(),
                'updated_at' => new \DateTimeImmutable(),
            ]);

            return (int)$this->connection->lastInsertId();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->connection->update('users', [
                    'username' => $userData['username'],
                    'first_name' => $userData['first_name'] ?? '',
                    'last_name' => $userData['last_name'] ?? '',
                    'status' => $userData['status'] ?? 'active',
                    'updated_at' => new \DateTimeImmutable(),
                ], [
                    'email' => $userData['email'],
                ]);

                $result = $this->connection->fetchAssociative(
                    'SELECT id FROM users WHERE email = ?',
                    [$userData['email']]
                );

                return (int)$result['id'];
            }

            throw new RuntimeException('DBAL user upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertOrIncrementStock(string $sku, int $additionalStock): bool
    {
        try {
            $this->connection->insert('products', [
                'sku' => $sku,
                'stock_quantity' => $additionalStock,
                'updated_at' => new \DateTimeImmutable(),
            ]);

            return true;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->connection->executeStatement(
                    'UPDATE products SET stock_quantity = stock_quantity + ?, updated_at = ? WHERE sku = ?',
                    [$additionalStock, new \DateTimeImmutable(), $sku]
                );

                return true;
            }

            throw new RuntimeException('DBAL increment stock failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
