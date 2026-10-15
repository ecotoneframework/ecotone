<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Modelling\WithEvents;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

#[Asynchronous('orders')]
#[Aggregate]
/**
 * licence Apache-2.0
 */
class Order
{
    use WithEvents;

    #[Identifier]
    private $orderId;

    private $isNotifiedCount = 0;

    private bool $isCancelled = false;

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
        $this->recordThat(new OrderWasPlaced($orderId));
    }

    #[CommandHandler('order.register', 'orderReceiver')]
    public static function register(PlaceOrder $placeOrder): self
    {
        return new self($placeOrder->getOrderId());
    }

    #[CommandHandler('order.cancel_from_metadata', 'orderCancelFromMetadata')]
    public function cancelFromMetadata(): void
    {
        $this->isCancelled = true;
        $this->recordThat(new OrderCancelled($this->orderId));
    }

    #[CommandHandler('order.cancel', 'orderCancel')]
    public function cancel(): void
    {
        $this->isCancelled = true;
    }

    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    #[EventHandler(endpointId:'orderPlaced')]
    public function notify(OrderWasPlaced $order): void
    {
        $this->isNotifiedCount++;
        $this->recordThat(new OrderWasNotified($this->orderId));
    }

    #[QueryHandler('order.getOrder')]
    public function getRegisteredOrder(): string
    {
        return $this->orderId;
    }

    #[QueryHandler('order.wasNotified')]
    public function getIsNotifiedCount(): int
    {
        return $this->isNotifiedCount;
    }
}
