<?php

declare(strict_types=1);

namespace Acme\Seed\AddressFormatter;

/**
 * Seed payload: format postal address from structured data.
 * Combines lines, handles missing fields gracefully.
 */
final class AddressFormatterSeed
{
    // <<<PAYLOAD:address_formatter>>>
    public function formatAddress(array $address): array
    {
        $lines = [];
        if (!empty($address['name'])) {
            $lines[] = $address['name'];
        }
        if (!empty($address['company'])) {
            $lines[] = $address['company'];
        }
        $street = trim(($address['street1'] ?? '') . ' ' . ($address['street2'] ?? ''));
        if ($street !== '') {
            $lines[] = $street;
        }
        $cityLine = trim(($address['city'] ?? ''));
        if (!empty($address['state'])) {
            $cityLine .= ', ' . $address['state'];
        }
        if (!empty($address['postal'])) {
            $cityLine .= ' ' . $address['postal'];
        }
        if ($cityLine !== '') {
            $lines[] = $cityLine;
        }
        if (!empty($address['country'])) {
            $lines[] = $address['country'];
        }
        return [
            'lines' => $lines,
            'line_count' => count($lines),
        ];
    }
    // <<<END-PAYLOAD>>>
}