<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

final class EloquentFetchRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function getAllUsers(): array
    {
        $results = $this->db::table('users')->select('id', 'username', 'email', 'first_name', 'last_name')->get();
        return array_map(fn($row) => (array)$row, $results->toArray());
    }

    public function getUsersToArray(): array
    {
        return $this->db::table('users')->select('id', 'username', 'email')->get()->toArray();
    }

    public function getUsersPluck(): array
    {
        return $this->db::table('users')->pluck('username', 'id')->toArray();
    }

    public function getUsersChunked(): array
    {
        $users = [];
        $this->db::table('users')->select('id', 'username', 'email')->chunk(100, function ($results) use (&$users) {
            foreach ($results as $row) {
                $users[] = (array)$row;
            }
        });
        return $users;
    }

    public function getUsersCursor(): array
    {
        $users = [];
        foreach ($this->db::table('users')->select('id', 'username', 'email')->cursor() as $row) {
            $users[] = (array)$row;
        }
        return $users;
    }
}
