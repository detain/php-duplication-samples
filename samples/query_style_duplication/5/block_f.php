<?php
declare(strict_types=1);

namespace App\Repository\Symfony;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class SymfonyQueryBuilderArticleRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $searchPattern = "%{$query}%";

            $qb->select('id', 'title', 'slug', 'excerpt', 'content', 'author_id', 'published_at', 'status')
                ->from('articles', 'a')
                ->where($qb->expr()->or(
                    $qb->expr()->like('a.title', ':query'),
                    $qb->expr()->like('a.content', ':query2'),
                    $qb->expr()->like('a.tags', ':query3')
                ))
                ->andWhere('a.status = :status')
                ->setParameter('query', $searchPattern)
                ->setParameter('query2', $searchPattern)
                ->setParameter('query3', $searchPattern)
                ->setParameter('status', 'published')
                ->orderBy('a.published_at', 'DESC')
                ->setMaxResults($limit)
                ->setFirstResult($offset);

            $stmt = $qb->execute();

            return $stmt->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchByTag(string $tag, int $limit = 20): array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $tagPattern = "%{$tag}%";

            $qb->select('a.id', 'a.title', 'a.slug', 'a.excerpt', 'a.author_id', 'a.published_at')
                ->from('articles', 'a')
                ->where($qb->expr()->like('a.tags', ':tag'))
                ->andWhere('a.status = :status')
                ->setParameter('tag', $tagPattern)
                ->setParameter('status', 'published')
                ->orderBy('a.published_at', 'DESC')
                ->setMaxResults($limit);

            $stmt = $qb->execute();

            return $stmt->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder tag search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function searchWithPagination(array $criteria, int $page = 1, int $perPage = 20): array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $qb->select('*')
                ->from('articles', 'a');

            if (!empty($criteria['query'])) {
                $searchPattern = "%{$criteria['query']}%";
                $qb->andWhere($qb->expr()->or(
                    $qb->expr()->like('a.title', ':query'),
                    $qb->expr()->like('a.content', ':query2')
                ))
                ->setParameter('query', $searchPattern)
                ->setParameter('query2', $searchPattern);
            }

            if (!empty($criteria['tag'])) {
                $tagPattern = "%{$criteria['tag']}%";
                $qb->andWhere($qb->expr()->like('a.tags', ':tag'))
                    ->setParameter('tag', $tagPattern);
            }

            if (!empty($criteria['author_id'])) {
                $qb->andWhere('a.author_id = :author_id')
                    ->setParameter('author_id', $criteria['author_id']);
            }

            if (!empty($criteria['status'])) {
                $qb->andWhere('a.status = :status')
                    ->setParameter('status', $criteria['status']);
            }

            $offset = ($page - 1) * $perPage;

            $qb->orderBy('a.published_at', 'DESC')
                ->setMaxResults($perPage)
                ->setFirstResult($offset);

            $stmt = $qb->execute();

            return $stmt->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new RuntimeException('QueryBuilder pagination search failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
