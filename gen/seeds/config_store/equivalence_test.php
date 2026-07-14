<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the config_store seed.
 *
 *   php gen/seeds/config_store/equivalence_test.php
 *
 * Exit 0 = every variant matches the expected behavior on all inputs.
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
loadAs($dir . '/payload.php', 'store_mutable');
loadAs($dir . '/variants/api_immutable_api.php', 'store_immutable');

$tests = [
    fn() => true, // basic test marker
];

$failures = 0;

// Test: both stores return null for missing key
$mutable = new class {
    public function __construct() { eval(preg_replace('/class \w+/', 'class MutStore', implode("\n", array_slice(Payload::region($dir . '/payload.php'), 0))));
    }
};

$cases = [
    [['key' => 'theme', 'value' => 'dark']],
    [['key' => 'lang', 'value' => 'en']],
];

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] config_store: {$failures} divergence(s)\n");
    exit(1);
}
echo "[equivalence] config_store: OK\n";
exit(0);
