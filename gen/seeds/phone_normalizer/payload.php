<?php

declare(strict_types=1);

namespace Acme\Seed\PhoneNormalizer;

/**
 * Seed payload: normalize phone numbers to E.164 format.
 * Handles various input formats and extracts digits.
 */
final class PhoneNormalizerSeed
{
    // <<<PAYLOAD:phone_normalizer>>>
    public function normalizePhone(string $phone, string $countryCode = '1'): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        $len = strlen($digits);
        if ($len === 10) {
            return '+' . $countryCode . $digits;
        }
        if ($len === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }
        if ($len === 12) {
            return '+' . $digits;
        }
        return null;
    }
    // <<<END-PAYLOAD>>>
}