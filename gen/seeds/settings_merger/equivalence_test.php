<?php
/**
 * Equivalence test for settings_merger.
 * Verifies settings merging with precedence.
 *
 * Run: php gen/seeds/settings_merger/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$merger = new $className();

$testCases = [
    [
        'settings' => [
            ['a' => 1, 'b' => 2],
            ['b' => 3, 'c' => 4],
        ],
        'expected' => ['a' => 1, 'b' => 3, 'c' => 4]
    ],
    [
        'settings' => [
            ['db' => ['host' => 'localhost', 'port' => 3306]],
            ['db' => ['port' => 5432]],
        ],
        'expected' => ['db' => ['host' => 'localhost', 'port' => 5432]]
    ],
    [
        'settings' => [
            ['level1' => ['level2' => ['a' => 1]]],
            ['level1' => ['level2' => ['b' => 2]]],
        ],
        'expected' => ['level1' => ['level2' => ['a' => 1, 'b' => 2]]]
    ],
    [
        'settings' => [
            ['keep' => 'this'],
            ['override' => 'value'],
        ],
        'expected' => ['keep' => 'this', 'override' => 'value']
    ],
];

foreach ($testCases as $i => $case) {
    $result = $merger->mergeSettings(...$case['settings']);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "settings_merger case {$i} mismatch";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}