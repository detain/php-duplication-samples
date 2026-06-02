<?php
declare(strict_types=1);

namespace App\Repository\ResultProcessing;

use PDO;

interface MappingStrategyInterface
{
    public function map(PDO $pdo, string $query, string $targetClass): array;
}

final class MappingStrategy implements MappingStrategyInterface
{
    public function map(PDO $pdo, string $query, string $targetClass): array
    {
        $stmt = $pdo->query($query);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToClass($row, $targetClass);
        }

        return $results;
    }

    private function mapToClass(array $row, string $className): object
    {
        $instance = new $className();

        foreach ($row as $key => $value) {
            $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($instance, $setter)) {
                $instance->$setter($value);
            }
        }

        return $instance;
    }
}
