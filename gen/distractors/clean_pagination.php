<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function page(array $items, int $page, int $perPage): array
    {
        $total = count($items);
        $pages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        return [
            'data' => array_slice($items, $offset, $perPage),
            'total' => $total,
            'pages' => $pages,
        ];
    }
}
