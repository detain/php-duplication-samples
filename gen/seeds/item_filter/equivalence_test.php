<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the item_filter seed.
 *
 *   php gen/seeds/item_filter/equivalence_test.php
 *
 * Exit 0 = every variant matches the pristine payload on all inputs.
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
loadAs($dir . '/payload.php', 'filter_pristine');

$variants = [
    'filter_collector_vs_filter' => $dir . '/variants/cf_collector_vs_filter.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$items = [
    [['sku' => 'A1', 'name' => 'Widget', 'price' => 10.00]],
    [['sku' => 'A2', 'name' => '', 'price' => 5.00]],
    [['name' => 'Gadget', 'price' => 0]],
    [['sku' => 'B1', 'name' => 'Gizmo', 'price' => 20.00]],
    [],
];

$failures = 0;
$count = 0;
foreach ($items as $ii => $itemList) {
    $expected = filter_pristine($itemList);
    foreach (array_keys($variants) as $fn) {
        $count++;
        $actual = @$fn($itemList);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] {$fn} items {$ii}: expected "
                . var_export($expected, true) . " got " . var_export($actual, true) . "\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] item_filter: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] item_filter: OK ({$count} input combinations across "
    . count($variants) . " variant(s))\n";
exit(0);
