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
loadAs($dir . '/payload.php', 'notify_pristine');

$variants = [
    'notify_queue' => $dir . '/variants/queue_based.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$testChannels = ['email', 'sms', 'push'];
$testRecipients = [
    'email' => ['valid@example.com', 'invalid-email', ''],
    'sms' => ['+14155551234', '123456', ''],
    'push' => ['valid_push_token_123', 'short', ''],
];
$testTemplate = 'Hello {{name}}!';
$testData = ['name' => 'Test User', 'subject' => 'Hello'];

$failures = 0;
$count = 0;

foreach ($testChannels as $channel) {
    foreach ($testRecipients[$channel] as $recipient) {
        $expected = @notify_pristine($channel, $recipient, $testTemplate, $testData);
        foreach (array_keys($variants) as $fn) {
            $count++;
            $actual = @$fn($channel, $recipient, $testTemplate, $testData);
            if (($actual['success'] ?? false) !== ($expected['success'] ?? false)) {
                $failures++;
                fwrite(STDERR, "[FAIL] {$fn} channel={$channel} recipient={$recipient}\n");
            }
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] notification_service: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] notification_service: OK ({$count} input combinations across " . count($variants) . " variant(s))\n";
exit(0);
