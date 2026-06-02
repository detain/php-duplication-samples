<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use RuntimeException;

final class SymfonyQueryBuilderUserRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findById(int $id): ?array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $qb->select('id', 'username', 'email', 'first_name', 'last_name', 'created_at', 'status')
                ->from('users', 'u')
                ->where('u.id = :id')
                ->andWhere('u.active = :active')
                ->setParameter('id', $id, \Doctrine\DBAL\ParameterType::INTEGER)
                ->setParameter('active', 1, \Doctrine\DBAL\ParameterType::INTEGER)
                ->setMaxResults(1);

            $stmt = $qb->execute();
            $result = $stmt->fetchAssociative();

            return $result ?: null;
        } catch (\Exception $e) {
            throw new RuntimeException('Symfony QueryBuilder query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }
}
