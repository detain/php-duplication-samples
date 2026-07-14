<?php
/**
 * Equivalence test for slug_generator.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/slug_generator/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$generator = new $className();

$testCases = [
    ['input' => 'Hello World', 'expected' => 'hello-world'],
    ['input' => 'This is a TEST', 'expected' => 'this-is-a-test'],
    ['input' => 'Special!@#Characters', 'expected' => 'specialcharacters'],
    ['input' => 'multiple   spaces', 'expected' => 'multiple-spaces'],
    ['input' => 'UPPERCASE', 'expected' => 'uppercase'],
    ['input' => '---dash-start---', 'expected' => 'dash-start'],
    ['input' => 'Mixed__underscores_and-dashes', 'expected' => 'mixed-underscores-and-dashes'],
];

foreach ($testCases as $i => $case) {
    $result = @$generator->slugify($case['input']);

    if (!is_string($result)) {
        $pass = false;
        $errors[] = "slug_generator case {$i} returned non-string";
        continue;
    }

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "slug_generator case {$i} returned '{$result}' expected '{$case['expected']}'";
    }

    // Verify deterministic
    $result2 = @$generator->slugify($case['input']);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "slug_generator case {$i} non-deterministic";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}