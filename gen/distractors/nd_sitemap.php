<?php

declare(strict_types=1);

namespace __NAMESPACE__;

/**
 * Sitemap URL entry builder.
 */
final class __CLASS__
{
    public function entry(string $loc, string $changefreq, float $priority): string
    {
        $priority = max(0.0, min(1.0, $priority));
        return sprintf(
            '<url><loc>%s</loc><changefreq>%s</changefreq><priority>%.1f</priority></url>',
            htmlspecialchars($loc, ENT_XML1),
            $changefreq,
            $priority
        );
    }

    public function document(array $entries): string
    {
        return '<urlset>' . implode('', $entries) . '</urlset>';
    }
}
