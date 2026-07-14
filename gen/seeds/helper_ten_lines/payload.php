<?php

declare(strict_types=1);

namespace Acme\Seed\HelperTenLines;

/**
 * Seed payload: 10-line array helper function. The clonable region between the
 * sentinels is lifted by the generator; this wrapper only makes the file valid
 * PHP for `php -l` and for the equivalence harness.
 */
final class HelperTenLinesSeed
{
    // <<<PAYLOAD:helper_ten_lines>>>
    public function filterNonEmpty(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if ($value !== null && $value !== '' && $value !== false) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
