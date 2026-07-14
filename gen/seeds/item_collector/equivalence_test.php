<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the item_collector seed.
 *
 *   php gen/seeds/item_collector/equivalence_test.php
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
loadAs($dir . '/payload.php', 'collect_pristine');

$variants = [
    'collect_loop_to_map' => $dir . '/variants/cf_loop_to_map.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$users = [
    [['id' => 1, 'name' => 'alice', 'role' => 'admin', 'active' => true]],
    [['id' => 2, 'name' => 'bob', 'role' => 'viewer', 'active' => false]],
    [['id' => 3, 'name' => 'carol', 'role' => 'editor', 'active' => true]],
    [['name' => 'dan', 'active' => true]],
    [['id' => 5, 'active' => true]],
    [],
];

$failures = 0;
$count = 0;
foreach ($users as $ui => $userList) {
    $expected = collect_pristine($userList);
    foreach (array_keys($variants) as $fn) {
        $count++;
        $actual = @$fn($userList);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] {$fn} userList {$ui}: expected "
                . var_export($expected, true) . " got " . var_export($actual, true) . "\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] item_collector: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] item_collector: OK ({$count} input combinations across "
    . count($variants) . " variant(s))\n";
exit(0);
