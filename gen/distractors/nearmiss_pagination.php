<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function paginate(array $items, int $page, int $perPage): array
    {
        $total = count($items);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $slice = array_slice($items, $offset, $perPage);

        return [
            'data' => $slice,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
            ],
        ];
    }

    public function cursorPaginate(array $items, string $cursor, int $perPage): array
    {
        $found = false;
        $startIndex = 0;

        if ($cursor !== '') {
            foreach ($items as $index => $item) {
                if ((string) ($item['id'] ?? $index) === $cursor) {
                    $found = true;
                    $startIndex = $index + 1;
                    break;
                }
            }
            if (!$found) {
                $startIndex = 0;
            }
        }

        $slice = array_slice($items, $startIndex, $perPage + 1);
        $hasMore = count($slice) > $perPage;
        if ($hasMore) {
            array_pop($slice);
        }

        $nextCursor = $hasMore && !empty($slice) ? (string) ($slice[count($slice) - 1]['id'] ?? '') : null;

        return [
            'data' => $slice,
            'meta' => [
                'per_page' => $perPage,
                'next_cursor' => $nextCursor,
                'has_more' => $hasMore,
            ],
        ];
    }
    // <<<END-NEARMISS>>>
}
