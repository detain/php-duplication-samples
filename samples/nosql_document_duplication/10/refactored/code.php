<?php
declare(strict_types=1);

namespace App\Database\NoSQL\Refactored;

interface CosmosRawQueryInterface
{
    public function rawQuery(string $sql, array $params = []): array;
}

final class CosmosRawQueryRepositoryFactory
{
    public static function create(array $config = []): CosmosRawQueryInterface
    {
        return new \App\Database\NoSQL\CosmosDBRawQueryRepository(
            $config['client'],
            $config['database'] ?? 'mydb',
            $config['collection'] ?? 'documents'
        );
    }
}
