<?php
declare(strict_types=1);

namespace Acme\Payment\Strategy;

interface PaymentStrategyInterface
{
    /**
     * Process payment for the given amount.
     * @param float $amount
     * @return bool
     */
    public function pay(float $amount): bool;

    /**
     * Get the payment method name.
     * @return string
     */
    public function getMethodName(): string;
}

class CreditCardPaymentStrategy implements PaymentStrategyInterface
{
    private string $cardNumber;
    private string $expiry;
    private string $cvv;

    public function __construct(string $cardNumber, string $expiry, string $cvv)
    {
        $this->cardNumber = $cardNumber;
        $this->expiry = $expiry;
        $this->cvv = $cvv;
    }

    public function pay(float $amount): bool
    {
        // Validate card number length (basic validation)
        if (strlen($this->cardNumber) < 13) {
            return false;
        }
        // Validate expiry format
        if (!preg_match('/^\d{2}\/\d{2}$/', $this->expiry)) {
            return false;
        }
        // Validate CVV length
        if (strlen($this->cvv) < 3 || strlen($this->cvv) > 4) {
            return false;
        }
        // Simulate payment processing
        $this->authorizeCard();
        $this->chargeCard($amount);
        return true;
    }

    public function getMethodName(): string
    {
        return 'credit_card';
    }

    private function authorizeCard(): void
    {
        // Card authorization logic
    }

    private function chargeCard(float $amount): void
    {
        // Card charging logic
    }
}

class PayPalPaymentStrategy implements PaymentStrategyInterface
{
    private string $email;
    private string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function pay(float $amount): bool
    {
        // Validate email format
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // Validate password not empty
        if (empty($this->password)) {
            return false;
        }
        // Simulate PayPal payment
        $this->authenticateUser();
        $this->processPayPalPayment($amount);
        return true;
    }

    public function getMethodName(): string
    {
        return 'paypal';
    }

    private function authenticateUser(): void
    {
        // PayPal authentication logic
    }

    private function processPayPalPayment(float $amount): void
    {
        // PayPal payment processing
    }
}

class BankTransferPaymentStrategy implements PaymentStrategyInterface
{
    private string $accountNumber;
    private string $routingNumber;
    private string $bankName;

    public function __construct(string $accountNumber, string $routingNumber, string $bankName)
    {
        $this->accountNumber = $accountNumber;
        $this->routingNumber = $routingNumber;
        $this->bankName = $bankName;
    }

    public function pay(float $amount): bool
    {
        // Validate account number
        if (strlen($this->accountNumber) < 8) {
            return false;
        }
        // Validate routing number (9 digits)
        if (!preg_match('/^\d{9}$/', $this->routingNumber)) {
            return false;
        }
        // Validate bank name not empty
        if (empty($this->bankName)) {
            return false;
        }
        // Simulate bank transfer
        $this->verifyBankAccount();
        $this->initiateTransfer($amount);
        return true;
    }

    public function getMethodName(): string
    {
        return 'bank_transfer';
    }

    private function verifyBankAccount(): void
    {
        // Bank account verification
    }

    private function initiateTransfer(float $amount): void
    {
        // Bank transfer initiation
    }
}