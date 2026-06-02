<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use RuntimeException;

final class MysqliFetchArrayRepository
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
        while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
            $users[] = [
                'id' => (int)$row[0],
                'username' => $row[1],
                'email' => $row[2],
                'first_name' => $row[3],
                'last_name' => $row[4],
            ];
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersAssoc(): array
    {
        $result = $this->connection->query('SELECT id, username, email FROM users');

        $users = [];
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $users[] = $row;
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersBoth(): array
    {
        $result = $this->connection->query('SELECT id, username, email FROM users');

        $users = [];
        while ($row = mysqli_fetch_array($result, MYSQLI_BOTH)) {
            $users[] = [
                'id' => (int)$row[0],
                'id_2' => (int)$row['id'],
                'username' => $row[1],
                'username_2' => $row['username'],
                'email' => $row[2],
            ];
        }

        mysqli_free_result($result);

        return $users;
    }

    public function getUsersMapped(): array
    {
        $result = $this->connection->query('SELECT id, username, email FROM users');

        $users = [];
        while (($row = mysqli_fetch_array($result, MYSQLI_NUM))) {
            $users[$row[0]] = [
                'username' => $row[1],
                'email' => $row[2],
            ];
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
