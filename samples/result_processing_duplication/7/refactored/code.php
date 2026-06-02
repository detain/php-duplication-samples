<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;

interface PaginationStrategyInterface
{
    public function paginate(PDO $pdo, int $page, int $perPage): array;
    public function paginateByKeyset(PDO $pdo, $lastId, int $perPage): array;
}

final class PaginationStrategy implements PaginationStrategyInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function paginate(PDO $pdo, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            'SELECT id, name, email FROM users LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function paginateByKeyset(PDO $pdo, $lastId, int $perPage): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, name, email FROM users WHERE id > :last_id ORDER BY id LIMIT :limit'
        );
        $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
