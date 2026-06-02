<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;
use ArrayIterator;
use LimitIterator;

final class ResultPagination
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function paginateWithOffset(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT id, name, email FROM users LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginateWithKeyset($lastId, int $perPage): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email FROM users WHERE id > :last_id ORDER BY id LIMIT :limit'
        );
        $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginateWithIterator(int $page, int $perPage): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email FROM users');

        $iterator = new LimitIterator(
            new ArrayIterator($stmt->fetchAll(PDO::FETCH_ASSOC)),
            ($page - 1) * $perPage,
            $perPage
        );

        return iterator_to_array($iterator);
    }

    public function paginateWithLimitAndOffset(int $offset, int $limit): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email FROM users LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
