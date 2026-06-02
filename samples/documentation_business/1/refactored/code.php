<?php

declare(strict_types=1);

namespace Billing\Subscriptions;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

/**
 * Unified subscription billing service
 *
 * Handles all subscription-related operations including:
 * - Subscription creation, renewal, and cancellation
 * - Tier upgrades and downgrades with prorated billing
 * - Payment processing and retry logic
 * - Billing dispute handling
 * - Refund calculations
 *
 * Business Rules:
 * - Subscriptions renew automatically on the billing date unless cancelled
 * - Failed payments trigger retry on days 1, 3, and 7 after the billing date
 * - Account suspension occurs 14 days after initial payment failure
 * - Prorated refunds are calculated based on days used in current period
 * - Annual subscriptions receive a 15% discount vs monthly billing
 * - Referral credits expire 90 days after being awarded
 * - Tier upgrades take effect immediately with prorated charge
 */
final class SubscriptionBillingService
{
    private const TIER_HIERARCHY = [
        'basic' => 1,
        'standard' => 2,
        'premium' => 3,
        'enterprise' => 4,
    ];

    private const RETRY_DAYS = [1, 3, 7];
    private const SUSPENSION_DAYS = 14;
    private const REFERRAL_CREDIT_EXPIRY_DAYS = 90;
    private const ANNUAL_DISCOUNT = 0.15;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly LoggerInterface $logger,
        private readonly PaymentGatewayInterface $paymentGateway
    ) {
    }

    /**
     * Process subscription renewal for all due subscriptions.
     *
     * @return array{processed: int, succeeded: int, failed: int, retried: int}
     */
    public function processRenewals(): array
    {
        $dueSubscriptions = $this->entityManager
            ->getRepository(Subscription::class)
            ->findDueForRenewal();

        $stats = ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'retried' => 0];

        foreach ($dueSubscriptions as $subscription) {
            $stats['processed']++;

            $result = $this->processRenewal($subscription);

            match ($result['status']) {
                'success' => $stats['succeeded']++,
                'retry' => $stats['retried']++,
                'failed' => $stats['failed']++
            };
        }

        $this->logger->info('Renewal processing completed', $stats);

        return $stats;
    }

    /**
     * Process a single subscription renewal
     */
    private function processRenewal(Subscription $subscription): array
    {
        try {
            $paymentResult = $this->paymentGateway->charge(
                $subscription->getCustomer(),
                $subscription->getBillingAmount()
            );

            if ($paymentResult->isSuccessful()) {
                $subscription->renew();
                $this->entityManager->flush();

                return ['status' => 'success'];
            }

            return $this->handleFailedPayment($subscription, $paymentResult);
        } catch (\Exception $e) {
            $this->logger->error('Renewal processing failed', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->handleFailedPayment($subscription, PaymentResult::failure($e->getMessage()));
        }
    }

    /**
     * Handle failed payment with retry logic
     */
    private function handleFailedPayment(Subscription $subscription, PaymentResult $result): array
    {
        $retryCount = $subscription->getRetryCount();

        if ($retryCount >= count(self::RETRY_DAYS)) {
            $subscription->suspend();
            $this->notifyCustomerOfSuspension($subscription);
            $this->entityManager->flush();

            return ['status' => 'failed', 'reason' => 'max_retries_exceeded'];
        }

        $nextRetryDays = self::RETRY_DAYS[$retryCount];
        $nextRetryDate = (new \DateTimeImmutable())->modify("+{$nextRetryDays} days");

        $subscription->incrementRetryCount();
        $subscription->setNextRetryDate($nextRetryDate);
        $this->entityManager->flush();

        $this->logger->info('Scheduled payment retry', [
            'subscription_id' => $subscription->getId(),
            'retry_count' => $retryCount + 1,
            'next_retry' => $nextRetryDate->format('c')
        ]);

        return ['status' => 'retry', 'next_retry' => $nextRetryDate];
    }

    /**
     * Calculate prorated refund when subscription is cancelled mid-period
     */
    public function calculateProratedRefund(Subscription $subscription): float
    {
        $periodStart = $subscription->getCurrentPeriodStart();
        $periodEnd = $subscription->getCurrentPeriodEnd();
        $totalDays = $periodStart->diff($periodEnd)->days;
        $daysRemaining = (new \DateTimeImmutable())->diff($periodEnd)->days;

        if ($daysRemaining <= 0) {
            return 0.0;
        }

        $proratedAmount = ($daysRemaining / $totalDays) * $subscription->getBillingAmount()->getAmount();

        $this->logger->info('Calculated prorated refund', [
            'subscription_id' => $subscription->getId(),
            'total_days' => $totalDays,
            'days_remaining' => $daysRemaining,
            'prorated_amount' => round($proratedAmount, 2)
        ]);

        return round($proratedAmount, 2);
    }

    /**
     * Upgrade subscription to a new plan tier
     */
    public function upgradeTier(Subscription $subscription, string $newTier): UpgradeResult
    {
        $currentTier = $subscription->getTier();

        if (!$this->isValidTier($newTier)) {
            throw new InvalidUpgradeException("Invalid tier: {$newTier}");
        }

        if (!$this->isHigherTier($newTier, $currentTier)) {
            throw new InvalidUpgradeException(
                "Cannot downgrade from {$currentTier} to {$newTier}. Use downgradeTier() instead."
            );
        }

        $creditAmount = $this->calculateUnusedCredit($subscription);

        $newPlan = $this->entityManager->getRepository(Plan::class)
            ->findOneBy(['tier' => $newTier, 'interval' => $subscription->getBillingInterval()]);

        $chargeAmount = max(0, $newPlan->getPrice() - $creditAmount);

        if ($chargeAmount > 0) {
            $paymentResult = $this->processPayment($subscription, $chargeAmount);
            if (!$paymentResult->isSuccessful()) {
                return UpgradeResult::failure($paymentResult->getError());
            }
        }

        $subscription->setTier($newTier);
        $subscription->setBillingAmount($newPlan->getPrice());
        $subscription->setUpgradedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return UpgradeResult::success($creditAmount, $chargeAmount, $newTier);
    }

    /**
     * Calculate unused credit for current billing period
     */
    public function calculateUnusedCredit(Subscription $subscription): float
    {
        $periodStart = $subscription->getCurrentPeriodStart();
        $periodEnd = $subscription->getCurrentPeriodEnd();
        $now = new \DateTimeImmutable();

        $totalDays = $periodStart->diff($periodEnd)->days;
        $daysUsed = $periodStart->diff($now)->days;
        $daysRemaining = max(0, $totalDays - $daysUsed);

        $dailyRate = $subscription->getBillingAmount()->getAmount() / $totalDays;

        return round($dailyRate * $daysRemaining, 2);
    }

    /**
     * Handle billing dispute for duplicate charges
     */
    public function handleDuplicateChargeDispute(int $customerId, array $chargeIds): DisputeResult
    {
        $customer = $this->entityManager->find(Customer::class, $customerId);

        $charges = $this->paymentGateway->getCharges($chargeIds);

        foreach ($charges as $charge) {
            if ($charge['source'] !== 'internal') {
                throw new \InvalidArgumentException('Charge not from internal system');
            }
        }

        $chargeDates = array_unique(array_map(
            fn($c) => $c['created_at']->format('Y-m-d'),
            $charges
        ));

        if (count($chargeDates) > 1) {
            $this->logger->info('Charges on different days, investigating further', [
                'customer_id' => $customerId,
                'charge_dates' => array_values($chargeDates)
            ]);
        }

        $duplicateAmount = array_sum(array_column($charges, 'amount'));

        $refundResult = $this->paymentGateway->refund($duplicateAmount);

        if (!$refundResult->isSuccessful()) {
            $this->logger->error('Refund processing failed', [
                'customer_id' => $customerId,
                'amount' => $duplicateAmount
            ]);
            return DisputeResult::failure('Refund processing failed');
        }

        $compensationMonths = 3;
        $monthlyAmount = $customer->getSubscription()->getBillingAmount()->getAmount() / 12;
        $compensationCredit = $monthlyAmount * $compensationMonths;

        $this->issueCompensationCredit($customer, $compensationCredit);

        $this->logDisputeResolution($customerId, $chargeIds, $duplicateAmount, $compensationCredit);

        return DisputeResult::success(
            $duplicateAmount,
            $compensationCredit,
            'Full refund processed. Compensation credit added to account.'
        );
    }

    /**
     * Issue compensation credit to customer account
     */
    private function issueCompensationCredit(Customer $customer, float $amount): void
    {
        $credit = new AccountCredit();
        $credit->setCustomer($customer);
        $credit->setAmount($amount);
        $credit->setType('compensation');
        $credit->setExpiresAt(new \DateTimeImmutable('+' . self::REFERRAL_CREDIT_EXPIRY_DAYS . ' days'));
        $credit->setReason('Duplicate charge dispute resolution');
        $credit->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($credit);
        $this->entityManager->flush();
    }

    /**
     * Log dispute resolution for audit
     */
    private function logDisputeResolution(
        int $customerId,
        array $chargeIds,
        float $refundAmount,
        float $compensationCredit
    ): void {
        $this->logger->info('Billing dispute resolved', [
            'customer_id' => $customerId,
            'charge_ids' => $chargeIds,
            'refund_amount' => $refundAmount,
            'compensation_credit' => $compensationCredit,
            'resolved_at' => date('c')
        ]);
    }

    /**
     * Process payment for subscription
     */
    private function processPayment(Subscription $subscription, float $amount): PaymentResult
    {
        return $this->paymentGateway->charge($subscription->getCustomer(), new Money($amount));
    }

    /**
     * Notify customer of account suspension
     */
    private function notifyCustomerOfSuspension(Subscription $subscription): void
    {
        $this->logger->info('Customer subscription suspended', [
            'customer_id' => $subscription->getCustomer()->getId(),
            'subscription_id' => $subscription->getId()
        ]);
    }

    /**
     * Check if tier is valid
     */
    private function isValidTier(string $tier): bool
    {
        return isset(self::TIER_HIERARCHY[$tier]);
    }

    /**
     * Check if new tier is higher than current tier
     */
    private function isHigherTier(string $newTier, string $currentTier): bool
    {
        return self::TIER_HIERARCHY[$newTier] > self::TIER_HIERARCHY[$currentTier];
    }

    /**
     * Calculate annual vs monthly pricing discount
     */
    public function calculateAnnualDiscount(float $monthlyPrice): float
    {
        $annualMonthlyTotal = $monthlyPrice * 12;
        $annualPrice = $annualMonthlyTotal * (1 - self::ANNUAL_DISCOUNT);

        return round($annualMonthlyTotal - $annualPrice, 2);
    }
}

/**
 * Payment result value object
 */
class PaymentResult
{
    private function __construct(
        private bool $successful,
        private ?string $error = null,
        private ?string $transactionId = null
    ) {
    }

    public static function success(string $transactionId = null): self
    {
        return new self(true, null, $transactionId);
    }

    public static function failure(string $error): self
    {
        return new self(false, $error);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}

/**
 * Upgrade result value object
 */
class UpgradeResult
{
    private function __construct(
        private bool $success,
        private ?string $error = null,
        private float $creditApplied = 0,
        private float $amountCharged = 0,
        private ?string $newTier = null
    ) {
    }

    public static function success(float $credit, float $charged, string $tier): self
    {
        return new self(true, null, $credit, $charged, $tier);
    }

    public static function failure(string $error): self
    {
        return new self(false, $error);
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Dispute result value object
 */
class DisputeResult
{
    private function __construct(
        private bool $success,
        private float $refundAmount = 0,
        private float $compensationCredit = 0,
        private ?string $message = null,
        private ?string $error = null
    ) {
    }

    public static function success(float $refund, float $compensation, string $message): self
    {
        return new self(true, $refund, $compensation, $message);
    }

    public static function failure(string $error): self
    {
        return new self(false, 0, 0, null, $error);
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}

/**
 * Interface for payment gateway integration
 */
interface PaymentGatewayInterface
{
    public function charge(Customer $customer, Money $amount): PaymentResult;
    public function refund(float $amount): PaymentResult;
    public function getCharges(array $chargeIds): array;
}

/**
 * Account credit for referrals and compensation
 */
class AccountCredit
{
    private ?int $id = null;
    private Customer $customer;
    private float $amount;
    private string $type;
    private \DateTimeImmutable $expiresAt;
    private string $reason;
    private \DateTimeImmutable $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $date): self
    {
        $this->expiresAt = $date;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $date): self
    {
        $this->createdAt = $date;
        return $this;
    }
}
