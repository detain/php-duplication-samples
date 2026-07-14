<?php

declare(strict_types=1);

namespace Acme\Seed\PaginationLinks;

/**
 * Seed payload: build pagination links with page numbers and URL templates.
 */
final class PaginationLinksSeed
{
    // <<<PAYLOAD:pagination_links>>>
    public function buildPagination(int $currentPage, int $totalPages, string $urlTemplate): array
    {
        if ($totalPages <= 1) {
            return ['pages' => [], 'has_prev' => false, 'has_next' => false, 'current' => 1];
        }
        $pages = [];
        $range = 2;
        $start = max(1, $currentPage - $range);
        $end = min($totalPages, $currentPage + $range);
        for ($p = $start; $p <= $end; $p++) {
            $url = str_replace('{page}', (string) $p, $urlTemplate);
            $pages[] = ['number' => $p, 'url' => $url, 'current' => $p === $currentPage];
        }
        return [
            'pages' => $pages,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'current' => $currentPage,
            'total' => $totalPages,
        ];
    }
    // <<<END-PAYLOAD>>>
}
