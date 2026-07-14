<?php

declare(strict_types=1);

namespace Acme\Payment\PayPal;

use RuntimeException;

final class PayPalPayment
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function charge(array $paymentMethod, float $amount, string $currency): array
    {
        $validation = $this->validate($paymentMethod);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $token = $this->tokenize($paymentMethod);
        if ($token === null) {
            return ['success' => false, 'error' => 'Tokenization failed'];
        }

        $result = $this->processCharge($token, $amount, $currency);

        if ($result['success']) {
            $this->recordTransaction('charge', $result['id'], $amount, $currency);
        }

        return $result;
    }

    public function refund(string $transactionId, ?float $amount = null): array
    {
        $transaction = $this->findTransaction($transactionId);
        if ($transaction === null) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        $refundAmount = $amount ?? $transaction['amount'];
        if ($refundAmount > $transaction['amount']) {
            return ['success' => false, 'error' => 'Refund amount exceeds original'];
        }

        $result = $this->processRefund($transactionId, $refundAmount);

        if ($result['success']) {
            $this->recordTransaction('refund', $result['id'], -$refundAmount, $transaction['currency']);
        }

        return $result;
    }

    public function handleWebhook(array $payload): bool
    {
        $type = $payload['type'] ?? '';
        $data = $payload['data'] ?? [];

        return match ($type) {
            'payment.succeeded' => $this->handlePaymentSucceeded($data),
            'payment.failed' => $this->handlePaymentFailed($data),
            'refund.created' => $this->handleRefundCreated($data),
            default => false,
        };
    }

    protected function validate(array $paymentMethod): array
    {
        if (empty($paymentMethod['token'])) {
            if (empty($paymentMethod['card_number'])) {
                return ['valid' => false, 'error' => 'Missing card token or number'];
            }
            if (!$this->luhnCheck($paymentMethod['card_number'])) {
                return ['valid' => false, 'error' => 'Invalid card number'];
            }
        }
        return ['valid' => true, 'error' => null];
    }

    protected function tokenize(array $paymentMethod): ?string
    {
        return bin2hex(random_bytes(16));
    }

    protected function processCharge(string $token, float $amount, string $currency): array
    {
        return [
            'success' => true,
            'id' => 'ch_' . bin2hex(random_bytes(8)),
            'amount' => $amount,
            'currency' => $currency,
        ];
    }

    protected function processRefund(string $transactionId, float $amount): array
    {
        return [
            'success' => true,
            'id' => 're_' . bin2hex(random_bytes(8)),
            'amount' => $amount,
        ];
    }

    protected function findTransaction(string $id): ?array
    {
        return null;
    }

    protected function recordTransaction(string $type, string $id, float $amount, string $currency): void
    {
    }

    protected function handlePaymentSucceeded(array $data): bool
    {
        return true;
    }

    protected function handlePaymentFailed(array $data): bool
    {
        return true;
    }

    protected function handleRefundCreated(array $data): bool
    {
        return true;
    }

    protected function luhnCheck(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$length - 1 - $i];
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        return $sum % 10 === 0;
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
