<?php
declare(strict_types=1);

namespace Acme\Text\Unified;

class SlugGenerator
{
    public function generateSlug(string $input, int $maxLength = 100): string
    {
        $slug = strtolower(trim($input));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($maxLength > 0 && strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
        }
        return $slug;
    }
}
