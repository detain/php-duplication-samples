<?php
declare(strict_types=1);

namespace Acme\Order\State;

interface OrderStateInterface
{
    /**
     * Handle the state-specific logic.
     * @param OrderContext $context
     * @return string
     */
    public function handle(OrderContext $context): string;

    /**
     * Get the current status string.
     * @return string
     */
    public function getStatus(): string;

    /**
     * Check if this state allows cancellation.
     * @return bool
     */
    public function canCancel(): bool;
}

class OrderContext
{
    private OrderStateInterface $state;
    private string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
        $this->state = new PendingState();
    }

    public function setState(OrderStateInterface $state): void
    {
        $this->state = $state;
    }

    public function getState(): OrderStateInterface
    {
        return $this->state;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function transition(): string
    {
        return $this->state->handle($this);
    }
}

class PendingState implements OrderStateInterface
{
    public function handle(OrderContext $context): string
    {
        $this->validateOrder($context->getOrderId());
        $context->setState(new ProcessingState());
        return 'Order is now being processed.';
    }

    public function getStatus(): string
    {
        return 'pending';
    }

    public function canCancel(): bool
    {
        return true;
    }

    private function validateOrder(string $orderId): void
    {
        // Pending order validation
    }
}

class ProcessingState implements OrderStateInterface
{
    public function handle(OrderContext $context): string
    {
        $this->processPayment($context->getOrderId());
        $this->reserveInventory($context->getOrderId());
        $context->setState(new ShippedState());
        return 'Order has been shipped.';
    }

    public function getStatus(): string
    {
        return 'processing';
    }

    public function canCancel(): bool
    {
        return true;
    }

    private function processPayment(string $orderId): void
    {
        // Payment processing logic
    }

    private function reserveInventory(string $orderId): void
    {
        // Inventory reservation logic
    }
}

class ShippedState implements OrderStateInterface
{
    public function handle(OrderContext $context): string
    {
        $this->updateShippingTracker($context->getOrderId());
        $context->setState(new DeliveredState());
        return 'Order has been delivered.';
    }

    public function getStatus(): string
    {
        return 'shipped';
    }

    public function canCancel(): bool
    {
        return false;
    }

    private function updateShippingTracker(string $orderId): void
    {
        // Shipping tracker update logic
    }
}

class DeliveredState implements OrderStateInterface
{
    public function handle(OrderContext $context): string
    {
        $this->completeDelivery($context->getOrderId());
        return 'Order delivery completed.';
    }

    public function getStatus(): string
    {
        return 'delivered';
    }

    public function canCancel(): bool
    {
        return false;
    }

    private function completeDelivery(string $orderId): void
    {
        // Delivery completion logic
    }
}

class CancelledState implements OrderStateInterface
{
    public function handle(OrderContext $context): string
    {
        $this->refundIfNeeded($context->getOrderId());
        $this->restoreInventory($context->getOrderId());
        return 'Order has been cancelled.';
    }

    public function getStatus(): string
    {
        return 'cancelled';
    }

    public function canCancel(): bool
    {
        return false;
    }

    private function refundIfNeeded(string $orderId): void
    {
        // Refund processing if applicable
    }

    private function restoreInventory(string $orderId): void
    {
        // Inventory restoration logic
    }
}