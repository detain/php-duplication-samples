<?php

declare(strict_types=1);

namespace Acme\Growth\Distract;

/**
 * Computes a numeric trust score for a user against a resource.
 */
final class GrowthDistractor
{
    public function trustScore(array $user, array $resource): int
    {
        $score = 0;
        if (isset($user['id'])) {
            $score += 10;
        }
        if (($user['status'] ?? '') === 'active') {
            $score += 20;
        }
        $score += 5 * count($user['roles'] ?? []);
        $score += 2 * count($user['grants'] ?? []);
        if (($resource['ownerId'] ?? null) === ($user['id'] ?? -1)) {
            $score += 40;
        }
        return min(100, $score);
    }

    public function tier(int $score): string
    {
        return $score >= 60 ? 'high' : 'low';
    }
}
