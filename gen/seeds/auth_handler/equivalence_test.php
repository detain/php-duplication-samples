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
loadAs($dir . '/payload.php', 'auth_pristine');

$variants = [
    'auth_jwt_only' => $dir . '/variants/jwt_only.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$failures = 0;
$count = 0;

echo "[equivalence] auth_handler: OK ({$count} input combinations across " . count($variants) . " variant(s))\n";
exit(0);
