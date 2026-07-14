<?php
/**
 * Equivalence test for diff_engine.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/diff_engine/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$diff = new $className();

$testCases = [
    [
        'from' => ['a' => 1, 'b' => 2],
        'to' => ['a' => 1, 'b' => 3],
        'expectedOps' => 1,
    ],
    [
        'from' => ['a' => 1],
        'to' => ['a' => 1, 'b' => 2],
        'expectedOps' => 1,
    ],
    [
        'from' => ['a' => 1, 'b' => 2],
        'to' => ['a' => 1],
        'expectedOps' => 1,
    ],
    [
        'from' => ['a' => 1, 'b' => ['x' => 1]],
        'to' => ['a' => 1, 'b' => ['x' => 2]],
        'expectedOps' => 1,
    ],
];

foreach ($testCases as $i => $case) {
    $result = @$diff->diff($case['from'], $case['to']);

    if (!is_array($result)) {
        $pass = false;
        $errors[] = "diff_engine case {$i} returned non-array";
        continue;
    }

    if (count($result) !== $case['expectedOps']) {
        $pass = false;
        $errors[] = "diff_engine case {$i} returned " . count($result) . " ops expected " . $case['expectedOps'];
    }

    // Verify deterministic
    $result2 = @$diff->diff($case['from'], $case['to']);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "diff_engine case {$i} non-deterministic";
    }
}

// Test apply() with 'add' operations (which use 'value')
$from = ['a' => 1];
$changes = [['op' => 'add', 'key' => 'b', 'value' => 2]];
$applied = $diff->apply($from, $changes);
if (!isset($applied['b']) || $applied['b'] !== 2) {
    $pass = false;
    $errors[] = "diff_engine apply(add) failed";
}

// Test apply() with 'remove' operations
$from2 = ['a' => 1, 'b' => 2];
$changes2 = [['op' => 'remove', 'key' => 'b']];
$applied2 = $diff->apply($from2, $changes2);
if (isset($applied2['b'])) {
    $pass = false;
    $errors[] = "diff_engine apply(remove) failed";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}