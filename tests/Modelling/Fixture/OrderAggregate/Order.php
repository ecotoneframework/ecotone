<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

#[Asynchronous("orders")]
#[Aggregate]
class Order
{
    use WithAggregateEvents;

    #[AggregateIdentifier]
    private $orderId;

    private $isNotifiedCount = 0;

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
        $this->recordThat(new OrderWasPlaced($orderId));
    }

    #[CommandHandler("order.register", "orderReceiver")]
    public static function register(PlaceOrder $placeOrder) : self
    {
        return new self($placeOrder->getOrderId());
    }

    #[EventHandler(endpointId:"orderPlaced")]
    public function notify(OrderWasPlaced $order) : void
    {
        $this->isNotifiedCount++;
        $this->recordThat(new OrderWasNotified($this->orderId));
    }

    #[QueryHandler("order.getOrder")]
    public function getRegisteredOrder() : string
    {
        return $this->orderId;
    }

    #[QueryHandler("order.wasNotified")]
    public function getIsNotifiedCount() : int
    {
        return $this->isNotifiedCount;
    }
}