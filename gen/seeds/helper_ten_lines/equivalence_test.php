<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the helper_ten_lines seed (§12.4).
 *
 * Run with:
 *   php gen/seeds/helper_ten_lines/equivalence_test.php
 */

namespace Acme\Seed\HelperTenLines;

final class EquivalenceTest
{
    public function filterNonEmpty(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if ($value !== null && $value !== '' && $value !== false) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}

$test = new EquivalenceTest();
$cases = [
    [['a' => 1, 'b' => null, 'c' => '', 'd' => false, 'e' => 0], ['a' => 1, 'e' => 0]],
    [['x' => 'hello', 'y' => null], ['x' => 'hello']],
    [['foo' => 42, 'bar' => '', 'baz' => 3.14], ['foo' => 42, 'baz' => 3.14]],
    [['a' => null], []],
    [['a' => ''], []],
    [['a' => false], []],
];

$failures = 0;
foreach ($cases as $i => [$input, $expected]) {
    $actual = $test->filterNonEmpty($input);
    if ($actual !== $expected) {
        fwrite(STDERR, "[equivalence] helper_ten_lines: case {$i} FAILED\n");
        $failures++;
    }
}
echo "[equivalence] helper_ten_lines: {$failures} failure(s)\n";
exit($failures > 0 ? 1 : 0);
