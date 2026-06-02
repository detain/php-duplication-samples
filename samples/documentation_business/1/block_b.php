<?php

declare(strict_types=1);

namespace Billing\Subscriptions;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscription entity representing a customer's recurring billing subscription
 *
 * Subscriptions track the customer's plan, billing cycle, payment status,
 * and handle automatic renewals and tier changes.
 *
 * @ORM\Entity(repositoryClass="SubscriptionRepository")
 * @ORM\Table(name="subscriptions")
 * @ORM\HasLifecycleCallbacks
 */
class Subscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';

    public const BILLING_MONTHLY = 'month';
    public const BILLING_ANNUAL = 'year';

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @var Customer
     * @ORM\ManyToOne(targetEntity="Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=false)
     */
    private Customer $customer;

    /**
     * @var Plan
     * @ORM\ManyToOne(targetEntity="Plan")
     * @ORM\JoinColumn(name="plan_id", referencedColumnName="id", nullable=false)
     */
    private Plan $plan;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    private string $status = self::STATUS_PENDING;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    private string $billingInterval = self::BILLING_MONTHLY;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $currentPeriodStart;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $currentPeriodEnd;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $billingDate = null;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private float $billingAmount = 0.0;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $tier = null;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $retryCount = 0;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $nextRetryDate = null;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $cancelledAt = null;

    /**
     * @var \DateTimeImmutable
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var \DateTimeImmutable|null
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $upgradedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;
        $this->tier = $plan->getTier();
        $this->billingAmount = $plan->getPrice();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getBillingInterval(): string
    {
        return $this->billingInterval;
    }

    public function setBillingInterval(string $interval): self
    {
        $this->billingInterval = $interval;
        return $this;
    }

    public function getCurrentPeriodStart(): \DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(\DateTimeImmutable $start): self
    {
        $this->currentPeriodStart = $start;
        return $this;
    }

    public function getCurrentPeriodEnd(): \DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(\DateTimeImmutable $end): self
    {
        $this->currentPeriodEnd = $end;
        return $this;
    }

    public function getBillingDate(): ?\DateTimeImmutable
    {
        return $this->billingDate;
    }

    public function setBillingDate(?\DateTimeImmutable $date): self
    {
        $this->billingDate = $date;
        return $this;
    }

    public function getBillingAmount(): Money
    {
        return new Money($this->billingAmount, 'USD');
    }

    public function setBillingAmount(float $amount): self
    {
        $this->billingAmount = $amount;
        return $this;
    }

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function setTier(string $tier): self
    {
        $this->tier = $tier;
        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): self
    {
        $this->retryCount++;
        return $this;
    }

    public function resetRetryCount(): self
    {
        $this->retryCount = 0;
        $this->nextRetryDate = null;
        return $this;
    }

    public function getNextRetryDate(): ?\DateTimeImmutable
    {
        return $this->nextRetryDate;
    }

    public function setNextRetryDate(?\DateTimeImmutable $date): self
    {
        $this->nextRetryDate = $date;
        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function cancel(): self
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUpgradedAt(): ?\DateTimeImmutable
    {
        return $this->upgradedAt;
    }

    public function getDaysRemainingInPeriod(): int
    {
        $now = new \DateTimeImmutable();
        return max(0, $now->diff($this->currentPeriodEnd)->days);
    }

    public function isDueForRenewal(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = new \DateTimeImmutable();
        return $now >= $this->currentPeriodEnd || $now >= $this->billingDate;
    }

    public function renew(): self
    {
        $interval = $this->billingInterval === self::BILLING_ANNUAL ? '1 year' : '1 month';

        $this->currentPeriodStart = $this->currentPeriodEnd;
        $this->currentPeriodEnd = $this->currentPeriodStart->modify('+' . $interval);
        $this->billingDate = $this->currentPeriodStart;
        $this->retryCount = 0;
        $this->nextRetryDate = null;

        return $this;
    }

    public function suspend(): self
    {
        $this->status = self::STATUS_SUSPENDED;
        return $this;
    }

    public function activate(): self
    {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

/**
 * Simple Money value object for billing amounts
 */
class Money
{
    public function __construct(
        private float $amount,
        private string $currency = 'USD'
    ) {
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): Money
    {
        if ($other->currency !== $this->currency) {
            throw new \InvalidArgumentException('Cannot add money with different currencies');
        }

        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if ($other->currency !== $this->currency) {
            throw new \InvalidArgumentException('Cannot subtract money with different currencies');
        }

        return new Money(max(0, $this->amount - $other->amount), $this->currency);
    }

    public function multiply(float $factor): Money
    {
        return new Money($this->amount * $factor, $this->currency);
    }
}
