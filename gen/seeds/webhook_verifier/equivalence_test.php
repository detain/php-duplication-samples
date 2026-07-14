<?php
/**
 * Equivalence test for webhook_verifier.
 * Verifies HMAC-SHA256 signature verification.
 *
 * Run: php gen/seeds/webhook_verifier/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$verifier = new $className();

$secret = 'webhook_secret_123';
$payload1 = '{"event": "order.created", "id": 123}';
$payload2 = '{"event": "order.updated", "id": 456}';
$validSig1 = 'sha256=' . hash_hmac('sha256', $payload1, $secret);
$validSig2 = 'sha256=' . hash_hmac('sha256', $payload2, $secret);

$testCases = [
    ['payload' => $payload1, 'signature' => $validSig1, 'secret' => $secret, 'expected' => true],
    ['payload' => $payload1, 'signature' => $validSig2, 'secret' => $secret, 'expected' => false],
    ['payload' => $payload1, 'signature' => 'sha256=invalid', 'secret' => $secret, 'expected' => false],
    ['payload' => $payload1, 'signature' => $validSig1, 'secret' => 'wrong_secret', 'expected' => false],
    ['payload' => $payload1, 'signature' => '', 'secret' => $secret, 'expected' => false],
    ['payload' => $payload1, 'signature' => $validSig1, 'secret' => '', 'expected' => false],
];

foreach ($testCases as $i => $case) {
    $result = $verifier->verifySignature($case['payload'], $case['signature'], $case['secret']);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "webhook_verifier case {$i}: expected " . var_export($case['expected'], true)
            . " got " . var_export($result, true);
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}