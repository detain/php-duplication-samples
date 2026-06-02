<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

interface ArangoRawQueryInterface
{
    public function rawQuery(string $aql, array $bindVars = []): array;
}

final class ArangoRawQueryRepositoryFactory
{
    public static function create(array $config = []): ArangoRawQueryInterface
    {
        return new \App\Database\NoSQL\ArangoDBRawQueryRepository($config['client']);
    }
}
