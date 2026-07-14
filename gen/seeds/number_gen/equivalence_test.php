<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the number_gen seed.
 *
 *   php gen/seeds/number_gen/equivalence_test.php
 *
 * Exit 0 = generator variant produces the same sequence as the array payload.
 */

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/m', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'generate_array');
loadAs($dir . '/variants/api_generator_api.php', 'generate_yield');

$cases = [
    [1, 5],
    [0, 3],
    [10, 1],
    [100, 10],
];

$failures = 0;
$count = 0;
foreach ($cases as $ci => [$start, $cnt]) {
    $expected = generate_array($start, $cnt);
    $gen = generate_yield($start, $cnt);
    $actual = is_object($gen) ? iterator_to_array($gen) : $gen;
    $count++;
    if ($actual !== $expected) {
        $failures++;
        fwrite(STDERR, "[FAIL] case {$ci}: expected " . var_export($expected, true)
            . " got " . var_export($actual, true) . "\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] number_gen: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] number_gen: OK ({$count} cases)\n";
exit(0);
