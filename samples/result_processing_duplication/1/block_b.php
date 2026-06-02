<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliFetchAssocRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);
        if ($this->connection->connect_error) {
            throw new RuntimeException('Connection failed: ' . $this->connection->connect_error);
        }
    }

    public function getAllUsers(): array
    {
        $result = $this->connection->query('SELECT id, username, email, first_name, last_name FROM users');

        if ($result === false) {
            throw new RuntimeException('Query failed: ' . $this->connection->error);
        }

        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersByKey(string $key): array
    {
        $result = $this->connection->query('SELECT id, username, email FROM users');

        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[$row[$key]] = $row;
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersIndexed(): array
    {
        $result = $this->connection->query('SELECT id, username, email FROM users');

        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[$row['id']] = $row;
        }

        mysqli_free_result($result);

        return $users;
    }

    public function __destruct()
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }
    }
}
