<?php
/**
 * Equivalence test for json_pointer.
 * Verifies JSON pointer path resolution.
 *
 * Run: php gen/seeds/json_pointer/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$resolver = new $className();

$document = [
    'foo' => ['bar' => 'baz', 'qux' => ['corge' => 'grault']],
    'foo-bar' => 'contains dash',
    'a/b' => 'contains slash',
    'a~b' => 'contains tilde',
    'array' => ['first', 'second', 'third'],
];

$testCases = [
    ['pointer' => '', 'expected' => $document],
    ['pointer' => '/foo', 'expected' => $document['foo']],
    ['pointer' => '/foo/bar', 'expected' => 'baz'],
    ['pointer' => '/foo/qux/corge', 'expected' => 'grault'],
    ['pointer' => '/array/0', 'expected' => 'first'],
    ['pointer' => '/array/2', 'expected' => 'third'],
    ['pointer' => '/foo-bar', 'expected' => 'contains dash'],
    ['pointer' => '/a~1b', 'expected' => 'contains slash'],
    ['pointer' => '/a~0b', 'expected' => 'contains tilde'],
    ['pointer' => '/notexist', 'expected' => null],
    ['pointer' => '/foo/notexist', 'expected' => null],
];

foreach ($testCases as $i => $case) {
    $result = $resolver->resolvePointer($document, $case['pointer']);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "json_pointer case {$i} ('{$case['pointer']}'): expected " . json_encode($case['expected']) . ", got " . json_encode($result);
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}