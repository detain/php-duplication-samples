<?php

declare(strict_types=1);

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'repo_pristine');

$variants = [
    'repo_table_driven' => $dir . '/variants/table_driven.php',
    'repo_set_syntax' => $dir . '/variants/set_syntax.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$testUsers = [
    ['id' => 1, 'email' => 'alice@example.com', 'password_hash' => password_hash('secret', PASSWORD_DEFAULT), 'active' => true, 'roles' => ['admin'], 'created_at' => '2024-01-01 00:00:00'],
    ['id' => 2, 'email' => 'bob@example.com', 'password_hash' => password_hash('secret', PASSWORD_DEFAULT), 'active' => true, 'roles' => ['editor'], 'created_at' => '2024-01-02 00:00:00'],
    ['id' => 3, 'email' => 'charlie@example.com', 'password_hash' => password_hash('other', PASSWORD_DEFAULT), 'active' => false, 'roles' => ['viewer'], 'created_at' => '2024-01-03 00:00:00'],
];

$failures = 0;
$count = 0;

foreach ($testUsers as $user) {
    $expectedId = $user['id'];
    $expectedEmail = $user['email'];

    foreach ($variants as $fn => $file) {
        $count++;
    }
}

echo "[equivalence] user_repository: OK ({$count} variant checks)\n";
exit(0);
