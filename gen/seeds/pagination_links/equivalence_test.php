<?php
/**
 * Equivalence test for pagination_links.
 * Verifies pagination link generation.
 *
 * Run: php gen/seeds/pagination_links/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$paginator = new $className();

$testCases = [
    ['currentPage' => 1, 'totalPages' => 5, 'urlTemplate' => '/page/{page}'],
    ['currentPage' => 3, 'totalPages' => 10, 'urlTemplate' => '/products?page={page}'],
    ['currentPage' => 1, 'totalPages' => 1, 'urlTemplate' => '/page/{page}'],
];

foreach ($testCases as $i => $case) {
    $result = $paginator->buildPagination($case['currentPage'], $case['totalPages'], $case['urlTemplate']);

    if (!isset($result['pages']) || !is_array($result['pages'])) {
        $pass = false;
        $errors[] = "pagination_links case {$i} missing pages array";
        continue;
    }

    if ($case['totalPages'] === 1 && count($result['pages']) !== 0) {
        $pass = false;
        $errors[] = "pagination_links case {$i} single page should have no pages";
    }

    $hasCurrent = false;
    foreach ($result['pages'] as $page) {
        if ($page['current'] && $page['number'] !== $case['currentPage']) {
            $pass = false;
            $errors[] = "pagination_links case {$i} current page mismatch";
        }
        if ($page['current']) $hasCurrent = true;
        if (strpos($page['url'], (string)$page['number']) === false) {
            $pass = false;
            $errors[] = "pagination_links case {$i} url missing page number";
        }
    }
    if (!$hasCurrent && $case['totalPages'] > 1) {
        $pass = false;
        $errors[] = "pagination_links case {$i} no current page marked";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}