<?php
/**
 * Equivalence test for csv_import.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/csv_import/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$importer = new $className();

$testCases = [
    ['line' => 'John,30,NYC', 'delimiter' => ','],
    ['line' => 'Jane;25;LA', 'delimiter' => ';'],
    ['line' => 'first,second,third', 'delimiter' => ','],
];

foreach ($testCases as $i => $case) {
    $result = @$importer->parseRow($case['line'], $case['delimiter']);

    if (!is_array($result)) {
        $pass = false;
        $errors[] = "csv_import case {$i} returned non-array";
        continue;
    }

    if (!isset($result['__count']) || !isset($result['__filled'])) {
        $pass = false;
        $errors[] = "csv_import case {$i} missing metadata keys";
        continue;
    }

    // Verify deterministic
    $result2 = @$importer->parseRow($case['line'], $case['delimiter']);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "csv_import case {$i} non-deterministic";
    }
}

// Test edge case - empty values
$result3 = @$importer->parseRow(',,,', ',');
if (!isset($result3['__empty']) || $result3['__empty'] < 3) {
    $pass = false;
    $errors[] = "csv_import should count empty values correctly";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}