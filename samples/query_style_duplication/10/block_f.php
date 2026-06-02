<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderUpsertRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function upsertProduct(array $productData): int
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $qb->insert('products')
                ->values([
                    'sku' => ':sku',
                    'name' => ':name',
                    'price' => ':price',
                    'stock_quantity' => ':stock',
                    'status' => ':status',
                    'created_at' => ':created',
                    'updated_at' => ':updated',
                ])
                ->setParameters([
                    'sku' => $productData['sku'],
                    'name' => $productData['name'],
                    'price' => (float)($productData['price'] ?? 0),
                    'stock' => (int)($productData['stock_quantity'] ?? 0),
                    'status' => $productData['status'] ?? 'active',
                    'created' => new \DateTimeImmutable(),
                    'updated' => new \DateTimeImmutable(),
                ])
                ->execute();

            return (int)$this->connection->lastInsertId();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->connection->createQueryBuilder()
                    ->update('products', 'p')
                    ->set('p.name', ':name')
                    ->set('p.price', ':price')
                    ->set('p.stock_quantity', ':stock')
                    ->set('p.status', ':status')
                    ->set('p.updated_at', ':updated')
                    ->where('p.sku = :sku')
                    ->setParameters([
                        'name' => $productData['name'],
                        'price' => (float)($productData['price'] ?? 0),
                        'stock' => (int)($productData['stock_quantity'] ?? 0),
                        'status' => $productData['status'] ?? 'active',
                        'updated' => new \DateTimeImmutable(),
                        'sku' => $productData['sku'],
                    ])
                    ->execute();

                $result = $this->connection->createQueryBuilder()
                    ->select('id')
                    ->from('products')
                    ->where('sku = :sku')
                    ->setParameter('sku', $productData['sku'])
                    ->execute()
                    ->fetchAssociative();

                return (int)$result['id'];
            }

            throw new RuntimeException('QueryBuilder upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertUser(array $userData): int
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $qb->insert('users')
                ->values([
                    'email' => ':email',
                    'username' => ':username',
                    'first_name' => ':first_name',
                    'last_name' => ':last_name',
                    'status' => ':status',
                    'created_at' => ':created',
                    'updated_at' => ':updated',
                ])
                ->setParameters([
                    'email' => $userData['email'],
                    'username' => $userData['username'],
                    'first_name' => $userData['first_name'] ?? '',
                    'last_name' => $userData['last_name'] ?? '',
                    'status' => $userData['status'] ?? 'active',
                    'created' => new \DateTimeImmutable(),
                    'updated' => new \DateTimeImmutable(),
                ])
                ->execute();

            return (int)$this->connection->lastInsertId();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->connection->createQueryBuilder()
                    ->update('users', 'u')
                    ->set('u.username', ':username')
                    ->set('u.first_name', ':first_name')
                    ->set('u.last_name', ':last_name')
                    ->set('u.status', ':status')
                    ->set('u.updated_at', ':updated')
                    ->where('u.email = :email')
                    ->setParameters([
                        'username' => $userData['username'],
                        'first_name' => $userData['first_name'] ?? '',
                        'last_name' => $userData['last_name'] ?? '',
                        'status' => $userData['status'] ?? 'active',
                        'updated' => new \DateTimeImmutable(),
                        'email' => $userData['email'],
                    ])
                    ->execute();

                $result = $this->connection->createQueryBuilder()
                    ->select('id')
                    ->from('users')
                    ->where('email = :email')
                    ->setParameter('email', $userData['email'])
                    ->execute()
                    ->fetchAssociative();

                return (int)$result['id'];
            }

            throw new RuntimeException('QueryBuilder user upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function upsertSettings(array $settings): bool
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $qb->insert('settings')
                ->values([
                    'setting_key' => ':key',
                    'setting_value' => ':value',
                    'updated_at' => ':updated',
                ])
                ->setParameters([
                    'key' => $settings['key'],
                    'value' => $settings['value'],
                    'updated' => new \DateTimeImmutable(),
                ])
                ->execute();

            return true;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->connection->createQueryBuilder()
                    ->update('settings', 's')
                    ->set('s.setting_value', ':value')
                    ->set('s.updated_at', ':updated')
                    ->where('s.setting_key = :key')
                    ->setParameters([
                        'value' => $settings['value'],
                        'updated' => new \DateTimeImmutable(),
                        'key' => $settings['key'],
                    ])
                    ->execute();

                return true;
            }

            throw new RuntimeException('QueryBuilder settings upsert failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
