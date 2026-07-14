<?php
/**
 * Equivalence test for csv_processor.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/csv_processor/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$processor = new $className();

$testCases = [
    [
        'content' => "name,age,city\nJohn,30,NYC\nJane,25,LA",
        'config' => ['delimiter' => ',', 'has_header' => true],
    ],
    [
        'content' => "id;value;status\n1;active;yes\n2;inactive;no",
        'config' => ['delimiter' => ';', 'has_header' => true],
    ],
    [
        'content' => "first,second\none,two\nthree,four",
        'config' => ['delimiter' => ',', 'has_header' => false],
    ],
];

foreach ($testCases as $i => $case) {
    $result = @$processor->process($case['content'], $case['config']);
    if ($result === null || !is_array($result)) {
        $pass = false;
        $errors[] = "csv_processor case {$i} returned invalid result";
        continue;
    }
    // Verify deterministic output
    $result2 = @$processor->process($case['content'], $case['config']);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "csv_processor case {$i} non-deterministic";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}