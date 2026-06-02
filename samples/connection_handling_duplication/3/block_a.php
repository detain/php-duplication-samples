<?php
declare(strict_types=1);

namespace App\Database\Connection\Examples;

use PDO;
use mysqli;
use Doctrine\DBAL\Connection;

final class ConnectionExamples
{
    public function pdoConnection(): PDO
    {
        $dsn = 'mysql:host=localhost;port=3306;dbname=test;charset=utf8mb4';
        return new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function mysqliConnection(): mysqli
    {
        $connection = new mysqli('localhost', 'root', '', 'test');
        $connection->set_charset('utf8mb4');
        return $connection;
    }

    public function doctrineConnection(): Connection
    {
        return \Doctrine\DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => 'test',
        ]);
    }

    public function mysqliProceduralConnection(): mysqli
    {
        $connection = mysqli_connect('localhost', 'root', '', 'test');
        mysqli_set_charset($connection, 'utf8mb4');
        return $connection;
    }

    public function pdoWithOptions(): PDO
    {
        $dsn = 'mysql:host=localhost;dbname=test;charset=utf8mb4';
        return new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
        ]);
    }
}
