<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the sorting_engine seed.
 *
 *   php gen/seeds/sorting_engine/equivalence_test.php
 *
 * Exit 0 = insertion sort variant produces the same sorted result as bubble sort payload.
 */

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'sort_bubble');
loadAs($dir . '/variants/sem_insertion_sort.php', 'sort_insertion');

$cases = [
    [3, 1, 4, 1, 5, 9, 2, 6],
    [9, 8, 7, 6, 5, 4, 3, 2, 1],
    [1],
    [2, 1],
    [],
    [5, 5, 5, 5],
    [1, 3, 2],
];

$failures = 0;
$count = 0;
foreach ($cases as $ci => $input) {
    $expected = sort_bubble($input);
    $actual = sort_insertion($input);
    $count++;
    if ($actual !== $expected) {
        $failures++;
        fwrite(STDERR, "[FAIL] case {$ci}: input [" . implode(',', $input) . "] expected ["
            . implode(',', $expected) . "] got [" . implode(',', $actual) . "]\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] sorting_engine: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] sorting_engine: OK ({$count} cases)\n";
exit(0);
