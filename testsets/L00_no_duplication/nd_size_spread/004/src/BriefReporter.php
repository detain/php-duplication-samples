<?php

declare(strict_types=1);

namespace Acme\Short\Reporter;

/**
 * Thumbnail dimension math.
 */
final class BriefReporter
{
    public function scaleToFit(int $width, int $height, int $box): array
    {
        $longest = max($width, $height);
        if ($longest <= $box) {
            return [$width, $height];
        }
        $factor = $box / $longest;
        return [(int) round($width * $factor), (int) round($height * $factor)];
    }

    public function aspectRatio(int $width, int $height): float
    {
        return $height === 0 ? 0.0 : round($width / $height, 3);
    }
}
