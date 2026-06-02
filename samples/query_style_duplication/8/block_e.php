<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderPaginationRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getPaginatedProducts(int $page = 1, int $perPage = 20, ?string $category = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        try {
            $countQb = $this->connection->createQueryBuilder();
            $countQb->select('COUNT(*)')
                ->from('products', 'p')
                ->innerJoin('p', 'categories', 'c', 'p.category_id = c.id')
                ->where('p.active = 1');

            if ($category !== null && $category !== '') {
                $countQb->andWhere('c.slug = :category')
                    ->setParameter('category', $category);
            }

            $total = (int)$countQb->execute()->fetchAssociative()['COUNT(*)'];

            $qb = $this->connection->createQueryBuilder();
            $qb->select('p.id', 'p.name', 'p.sku', 'p.price', 'p.stock_quantity', 'p.status',
                        'c.name as category_name', 'c.slug as category_slug')
                ->from('products', 'p')
                ->innerJoin('p', 'categories', 'c', 'p.category_id = c.id')
                ->where('p.active = 1')
                ->orderBy('p.created_at', 'DESC')
                ->setMaxResults($perPage)
                ->setFirstResult($offset);

            if ($category !== null && $category !== '') {
                $qb->andWhere('c.slug = :category')
                    ->setParameter('category', $category);
            }

            $products = $qb->execute()->fetchAllAssociative();

            return [
                'items' => $products,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPaginatedSearch(string $query, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $searchPattern = "%{$query}%";

        try {
            $countQb = $this->connection->createQueryBuilder();
            $countQb->select('COUNT(*)')
                ->from('products', 'p')
                ->where('p.active = 1')
                ->andWhere($countQb->expr()->or(
                    $countQb->expr()->like('p.name', ':query'),
                    $countQb->expr()->like('p.description', ':query2')
                ))
                ->setParameter('query', $searchPattern)
                ->setParameter('query2', $searchPattern);

            $total = (int)$countQb->execute()->fetchAssociative()['COUNT(*)'];

            $qb = $this->connection->createQueryBuilder();
            $qb->select('id', 'name', 'sku', 'price', 'stock_quantity', 'status')
                ->from('products', 'p')
                ->where('p.active = 1')
                ->andWhere($qb->expr()->or(
                    $qb->expr()->like('p.name', ':query'),
                    $qb->expr()->like('p.description', ':query2')
                ))
                ->setParameter('query', $searchPattern)
                ->setParameter('query2', $searchPattern)
                ->orderBy('p.name', 'ASC')
                ->setMaxResults($perPage)
                ->setFirstResult($offset);

            $products = $qb->execute()->fetchAllAssociative();

            return [
                'items' => $products,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
                'query' => $query,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder search pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getKeysetPagination(int $lastId = 0, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        try {
            $qb = $this->connection->createQueryBuilder();
            $qb->select('id', 'name', 'sku', 'price', 'created_at')
                ->from('products', 'p')
                ->where('p.active = 1')
                ->andWhere('p.id > :last_id')
                ->setParameter('last_id', $lastId)
                ->orderBy('p.id', 'ASC')
                ->setMaxResults($limit);

            $items = $qb->execute()->fetchAllAssociative();

            return [
                'items' => $items,
                'next_cursor' => !empty($items) ? (int)end($items)['id'] : $lastId,
                'has_more' => count($items) === $limit,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder keyset pagination failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
