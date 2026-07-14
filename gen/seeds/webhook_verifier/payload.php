<?php

declare(strict_types=1);

namespace Acme\Seed\WebhookVerifier;

/**
 * Seed payload: verify webhook HMAC-SHA256 signatures.
 * Compares expected vs computed signature using timing-safe comparison.
 */
final class WebhookVerifierSeed
{
    // <<<PAYLOAD:webhook_verifier>>>
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
    // <<<END-PAYLOAD>>>
}