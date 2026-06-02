<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class FluentUserRepository
{
    public function findById(int $id): ?array
    {
        try {
            $result = DB::table('users')
                ->select('id', 'username', 'email', 'first_name', 'last_name', 'created_at', 'status')
                ->where('id', $id)
                ->where('active', 1)
                ->limit(1)
                ->first();

            return $result ? (array) $result : null;
        } catch (\Exception $e) {
            throw new RuntimeException('Fluent query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByUsername(string $username): ?array
    {
        try {
            $result = DB::table('users')
                ->select('id', 'username', 'email', 'first_name', 'last_name', 'created_at', 'status')
                ->where('username', $username)
                ->where('active', 1)
                ->limit(1)
                ->first();

            return $result ? (array) $result : null;
        } catch (\Exception $e) {
            throw new RuntimeException('Fluent username query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
