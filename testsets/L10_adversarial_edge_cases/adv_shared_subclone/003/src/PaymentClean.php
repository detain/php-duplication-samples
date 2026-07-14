<?php

declare(strict_types=1);

namespace Acme\Payment\Config;

final class PaymentClean
{
    public function processPayment(array $paymentData): bool
    {
        return true;
    }

    public function refundPayment(string $transactionId): bool
    {
        return true;
    }
}
