<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliFetchObjectRepository
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

        $users = [];
        while ($row = mysqli_fetch_object($result)) {
            $users[] = $row;
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersAsClass(string $className = 'stdClass'): array
    {
        $result = $this->connection->query('SELECT id, username, email FROM users');

        $users = [];
        while ($row = mysqli_fetch_object($result, $className)) {
            $users[] = $row;
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersTyped(): array
    {
        $result = $this->connection->query('SELECT id, username, email, first_name, last_name FROM users');

        $users = [];
        while ($row = mysqli_fetch_object($result, User::class)) {
            $users[] = $row;
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

class User
{
    public int $id;
    public string $username;
    public string $email;
    public string $first_name;
    public string $last_name;
}
