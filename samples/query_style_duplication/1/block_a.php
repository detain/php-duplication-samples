<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use mysqli_result;
use RuntimeException;

final class MysqliProceduralUserRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = mysqli_connect($host, $username, $password, $database);

        if ($this->connection === false) {
            throw new RuntimeException(
                'MySQL connection failed: ' . mysqli_connect_error()
            );
        }

        if (!mysqli_set_charset($this->connection, 'utf8mb4')) {
            throw new RuntimeException('Failed to set charset: ' . mysqli_error($this->connection));
        }
    }

    public function findById(int $id): ?array
    {
        $id = mysqli_real_escape_string($this->connection, (string)$id);

        $sql = "SELECT id, username, email, first_name, last_name, created_at, status
                FROM users
                WHERE id = '{$id}' AND active = 1
                LIMIT 1";

        $result = mysqli_query($this->connection, $sql);

        if ($result === false) {
            throw new RuntimeException('Query failed: ' . mysqli_error($this->connection));
        }

        if (!$result instanceof mysqli_result) {
            return null;
        }

        $user = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        return $user ?: null;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            mysqli_close($this->connection);
        }
    }
}
