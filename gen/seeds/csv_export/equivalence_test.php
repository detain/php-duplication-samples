<?php
/**
 * Equivalence test for csv_export.
 * Verifies CSV export with proper escaping.
 *
 * Run: php gen/seeds/csv_export/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$exporter = new $className();

$testCases = [
    [
        'records' => [['name' => 'John', 'age' => '30']],
        'headers' => ['name', 'age'],
        'delimiter' => ',',
        'expectPattern' => '/John,30/'
    ],
    [
        'records' => [['name' => 'Doe, John', 'city' => 'NYC']],
        'headers' => ['name', 'city'],
        'delimiter' => ',',
        'expectPattern' => '/"Doe, John"/'
    ],
    [
        'records' => [['text' => 'Say "Hello"']],
        'headers' => ['text'],
        'delimiter' => ',',
        'expectPattern' => '/""Hello""/'
    ],
    [
        'records' => [],
        'headers' => ['col1', 'col2'],
        'delimiter' => ',',
        'expectPattern' => '/col1,col2/'
    ],
];

foreach ($testCases as $i => $case) {
    $result = @$exporter->exportToCsv($case['records'], $case['headers'], $case['delimiter']);

    if (!is_string($result)) {
        $pass = false;
        $errors[] = "csv_export case {$i} returned non-string";
        continue;
    }

    if (!preg_match($case['expectPattern'], $result)) {
        $pass = false;
        $errors[] = "csv_export case {$i} output mismatch: $result";
    }

    // Verify determinism
    $result2 = @$exporter->exportToCsv($case['records'], $case['headers'], $case['delimiter']);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "csv_export case {$i} non-deterministic";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}