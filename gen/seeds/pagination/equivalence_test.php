<?php
/**
 * Equivalence test for pagination.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/pagination/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$paginator = new $className();

$items = array_map(fn($i) => ['id' => $i, 'name' => "Item $i"], range(1, 25));

// Test paginate()
$result = $paginator->paginate($items, 1, 10);
if (!isset($result['data']) || !isset($result['meta']['total']) || !isset($result['meta']['current_page'])) {
    $pass = false;
    $errors[] = "pagination paginate() missing required keys";
}

// Verify first page has 10 items
if (count($result['data']) !== 10) {
    $pass = false;
    $errors[] = "pagination first page should have 10 items, got " . count($result['data']);
}

// Verify meta data
if ($result['meta']['total'] !== 25 || $result['meta']['total_pages'] !== 3) {
    $pass = false;
    $errors[] = "pagination meta total/total_pages incorrect";
}

// Test deterministic - same page twice
$result2 = $paginator->paginate($items, 1, 10);
if ($result['data'] !== $result2['data']) {
    $pass = false;
    $errors[] = "pagination non-deterministic output";
}

// Test last page
$result3 = $paginator->paginate($items, 3, 10);
if (count($result3['data']) !== 5) {
    $pass = false;
    $errors[] = "pagination last page should have 5 items, got " . count($result3['data']);
}

// Test out of bounds page
$result4 = $paginator->paginate($items, 10, 10);
if ($result4['meta']['current_page'] !== 3) {
    $pass = false;
    $errors[] = "pagination out-of-bounds page should clamp to max";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}