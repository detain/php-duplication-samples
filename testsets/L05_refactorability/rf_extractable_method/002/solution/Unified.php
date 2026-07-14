<?php
declare(strict_types=1);

namespace Acme\Cache\Unified;

class CacheKeyGenerator
{
    public function generateKey(string $prefix, string $id, array $scope = []): string
    {
        $key = $prefix . ':' . $id;
        if (!empty($scope)) {
            $key .= ':' . implode(':', $scope);
        }
        return $key;
    }
}
