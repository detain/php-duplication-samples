<?php
declare(strict_types=1);

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

final class DoctrineFetchRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getAllUsers(): array
    {
        $stmt = $this->connection->query('SELECT id, username, email, first_name, last_name FROM users');
        return $stmt->fetchAllAssociative();
    }

    public function getUsersIndexed(): array
    {
        $stmt = $this->connection->query('SELECT id, username, email FROM users');
        return $stmt->fetchAllAssociativeIndexed();
    }

    public function getUsersColumn(): array
    {
        $stmt = $this->connection->query('SELECT username FROM users');
        return $stmt->fetchFirstColumn();
    }

    public function getUsersKeyPair(): array
    {
        $stmt = $this->connection->query('SELECT id, username FROM users');
        return $stmt->fetchAllKeyValue();
    }

    public function getUsersNum(): array
    {
        $stmt = $this->connection->query('SELECT id, username, email FROM users');
        return $stmt->fetchAllNumeric();
    }

    public function getUserGroups(): array
    {
        $stmt = $this->connection->query('SELECT status, id, username FROM users');
        return $stmt->fetchAllGrouped();
    }
}
