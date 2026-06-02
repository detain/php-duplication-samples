<?php
declare(strict_types=1);

namespace App\Repository\Legacy;

use mysqli;
use mysqli_result;
use RuntimeException;

final class MysqliOopUserRepository
{
    private mysqli $connection;

    public function __construct(string $host, string $username, string $password, string $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);

        if ($this->connection->connect_error) {
            throw new RuntimeException(
                'MySQL connection failed: ' . $this->connection->connect_error
            );
        }

        if (!$this->connection->set_charset('utf8mb4')) {
            throw new RuntimeException('Failed to set charset: ' . $this->connection->error);
        }
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT id, username, email, first_name, last_name, created_at, status
             FROM users
             WHERE id = ? AND active = 1
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException('Prepare failed: ' . $this->connection->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result === false) {
            $stmt->close();
            return null;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    public function __destruct()
    {
        if (isset($this->connection) && $this->connection instanceof mysqli) {
            $this->connection->close();
        }
    }
}
