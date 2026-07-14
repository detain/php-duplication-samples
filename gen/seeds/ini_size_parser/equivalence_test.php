<?php
/**
 * Equivalence test for ini_size_parser.
 * Verifies INI size string parsing.
 *
 * Run: php gen/seeds/ini_size_parser/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$parser = new $className();

$testCases = [
    ['size' => '1K', 'expected' => 1024],
    ['size' => '1M', 'expected' => 1048576],
    ['size' => '1G', 'expected' => 1073741824],
    ['size' => '1T', 'expected' => 1099511627776],
    ['size' => '512K', 'expected' => 524288],
    ['size' => '2M', 'expected' => 2097152],
    ['size' => '100', 'expected' => 100],
    ['size' => '0', 'expected' => 0],
    ['size' => '1.5M', 'expected' => 1572864],
    ['size' => '', 'expected' => 0],
];

foreach ($testCases as $i => $case) {
    $result = $parser->parseIniSize($case['size']);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "ini_size_parser case {$i}: expected {$case['expected']}, got $result";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}