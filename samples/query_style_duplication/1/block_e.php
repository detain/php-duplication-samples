<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DoctrineException;
use RuntimeException;

final class DbalUserRepository
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
        } catch (DoctrineException $e) {
            throw new RuntimeException('Doctrine DBAL connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT id, username, email, first_name, last_name, created_at, status
                FROM users
                WHERE id = :id AND active = 1
                LIMIT 1';

        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery(['id' => $id]);
            $user = $result->fetchAssociative();

            return $user ?: null;
        } catch (DoctrineException $e) {
            throw new RuntimeException('Doctrine query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
