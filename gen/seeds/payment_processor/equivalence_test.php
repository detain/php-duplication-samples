<?php

declare(strict_types=1);

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/m', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'payment_pristine');

$variants = [
    'payment_strategy' => $dir . '/variants/strategy_object.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$testPaymentMethods = [
    ['token' => 'tok_valid123'],
    ['card_number' => '4111111111111111'],
    ['card_number' => '1234567890123456'],
    ['token' => ''],
];

$testAmounts = [10.00, 99.99, 1000.00];
$testCurrencies = ['USD', 'EUR'];

$failures = 0;
$count = 0;

foreach ($testPaymentMethods as $pi => $pm) {
    foreach ($testAmounts as $amount) {
        foreach ($testCurrencies as $currency) {
            $expected = @payment_pristine($pm, $amount, $currency);
            foreach (array_keys($variants) as $fn) {
                $count++;
                $actual = @$fn($pm, $amount, $currency);
                if ($actual !== $expected) {
                    $failures++;
                    fwrite(STDERR, "[FAIL] {$fn} pm={$pi} amount={$amount} currency={$currency}\n");
                }
            }
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] payment_processor: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] payment_processor: OK ({$count} input combinations across " . count($variants) . " variant(s))\n";
exit(0);
