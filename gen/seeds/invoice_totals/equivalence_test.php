<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the invoice_totals seed (§12.4).
 *
 * Runs the pristine payload and every variant against the same inputs and
 * asserts identical outputs. Run standalone:
 *
 *   php gen/seeds/invoice_totals/equivalence_test.php
 *
 * Exit 0 = all variants equivalent; exit 1 = a divergence (with details).
 */

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

/** Lift a payload region, turn it into a standalone renamed function, eval it. */
function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/m', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'inv_pristine');
loadAs($dir . '/variants/api_map_loop.php', 'inv_map');
loadAs($dir . '/variants/api_strings.php', 'inv_strings');
loadAs($dir . '/variants/api_regex_string.php', 'inv_regex_string');
loadAs($dir . '/variants/api_recursion.php', 'inv_recursion');
loadAs($dir . '/variants/api_builtins.php', 'inv_builtins');
loadAs($dir . '/variants/api_serialization.php', 'inv_serialization');

$cases = [
    [[], 0.07, 0.0],
    [[['qty' => 2, 'unitPrice' => 10.0]], 0.07, 0.1],
    [[['qty' => 2, 'unitPrice' => 10.0], ['qty' => 1, 'unitPrice' => 5.5]], 0.08, 0.0],
    [[['qty' => 3, 'unitPrice' => 3.33], ['qty' => 10, 'unitPrice' => 0.99], ['qty' => 1, 'unitPrice' => 100.0]], 0.2, 0.15],
    [[['qty' => 0, 'unitPrice' => 42.0]], 0.0, 0.5],
];

$variants = ['inv_map', 'inv_strings', 'inv_regex_string', 'inv_recursion', 'inv_builtins', 'inv_serialization'];
$failures = 0;

foreach ($cases as $i => [$items, $rate, $discount]) {
    $expected = inv_pristine($items, $rate, $discount);
    foreach ($variants as $fn) {
        $actual = @$fn($items, $rate, $discount);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] case {$i} variant {$fn}: expected "
                . json_encode($expected) . " got " . json_encode($actual) . "\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] invoice_totals: {$failures} divergence(s)\n");
    exit(1);
}
echo "[equivalence] invoice_totals: OK (" . count($cases) . " cases x " . count($variants) . " variant(s))\n";
exit(0);
