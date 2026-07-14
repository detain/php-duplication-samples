<?php

declare(strict_types=1);

namespace Acme\Seed\LegacyOrderExport;

/**
 * Seed payload: export order data to legacy pipe-delimited format.
 * Format: ID|CUSTOMER|DATE|TOTAL|STATUS|ITEMS_COUNT
 */
final class LegacyOrderExportSeed
{
    // <<<PAYLOAD:legacy_order_export>>>
    public function exportLegacyFormat(array $order): string
    {
        $id = $order['id'] ?? '';
        $customer = $this->escape($order['customer'] ?? '');
        $date = date('Y-m-d', $order['created_at'] ?? time());
        $total = number_format($order['total'] ?? 0.0, 2, '.', '');
        $status = strtoupper($order['status'] ?? 'PENDING');
        $itemCount = is_array($order['items'] ?? null) ? count($order['items']) : 0;
        return implode('|', [$id, $customer, $date, $total, $status, $itemCount]);
    }

    private function escape(string $value): string
    {
        if (strpos($value, '|') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace(['"', "\n"], ["''", ' '], $value) . '"';
        }
        return $value;
    }
    // <<<END-PAYLOAD>>>
}
