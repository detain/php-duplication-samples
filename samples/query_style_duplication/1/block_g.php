<?php
declare(strict_types=1);

namespace App\Repository\Laravel;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class EloquentUserRepository
{
    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        try {
            $result = $this->db::table('users')
                ->select('id', 'username', 'email', 'first_name', 'last_name', 'created_at', 'status')
                ->where('id', '=', $id)
                ->where('active', '=', 1)
                ->limit(1)
                ->first();

            if ($result === null) {
                return null;
            }

            return (array) $result;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByIdWithBuilder(int $id): ?array
    {
        try {
            $builder = $this->db::table('users')
                ->select('id', 'username', 'email', 'first_name', 'last_name', 'created_at', 'status')
                ->where('id', $id)
                ->whereActive(1);

            $sql = $builder->toSql();
            $result = $builder->first();

            return $result ? (array) $result : null;
        } catch (\Exception $e) {
            throw new RuntimeException('Eloquent builder query failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
