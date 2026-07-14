<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the tree_walker seed.
 *
 *   php gen/seeds/tree_walker/equivalence_test.php
 *
 * Exit 0 = every variant matches the pristine payload on all inputs.
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
loadAs($dir . '/payload.php', 'walk_pristine');

$variants = [
    'walk_iterative' => $dir . '/variants/cf_iterative_walk.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$trees = [
    ['value' => 'leaf-a', 'children' => []],
    ['value' => 'node-1', 'children' => [
        ['value' => 'leaf-b', 'children' => []],
        ['value' => 'leaf-c', 'children' => []],
    ]],
    ['value' => 'root', 'children' => [
        ['value' => 'l1', 'children' => [
            ['value' => 'l2', 'children' => []],
        ]],
    ]],
    ['value' => 'solo'],
    [],
];

$failures = 0;
$count = 0;
foreach ($trees as $ti => $tree) {
    $leaves = [];
    $expected = walk_pristine($tree, $leaves);
    foreach (array_keys($variants) as $fn) {
        $count++;
        $l = [];
        $actual = @$fn($tree, $l);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] {$fn} tree {$ti}: expected "
                . var_export($expected, true) . " got " . var_export($actual, true) . "\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] tree_walker: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] tree_walker: OK ({$count} input combinations across "
    . count($variants) . " variant(s))\n";
exit(0);
