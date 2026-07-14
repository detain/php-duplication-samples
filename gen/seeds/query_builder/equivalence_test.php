<?php
/**
 * Equivalence test for query_builder.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/query_builder/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$builder = new $className();

// Test select
$result = $builder->select('users', ['*'])->where('active', '=', 1)->orderBy('created', 'DESC')->limit(10)->build();
if (!isset($result['sql']) || !isset($result['params'])) {
    $pass = false;
    $errors[] = "query_builder select() returned invalid result";
}

// Verify deterministic build
$builder2 = new $className();
$result2 = $builder2->select('users', ['*'])->where('active', '=', 1)->orderBy('created', 'DESC')->limit(10)->build();
if ($result['sql'] !== $result2['sql'] || $result['params'] !== $result2['params']) {
    $pass = false;
    $errors[] = "query_builder non-deterministic build";
}

// Test simple select
$builder3 = new $className();
$simple = $builder3->select('products', ['id', 'name'])->build();
if (!isset($simple['sql']) || strpos($simple['sql'], 'SELECT') === false) {
    $pass = false;
    $errors[] = "query_builder simple select failed";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}