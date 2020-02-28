<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId\AddUserId;

/**
 * Class OrderService
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 * @AddUserId()
 * @Async(channelName="orders")
 */
class Order
{
    use WithAggregateEvents;

    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $orderId;

    private $isNotified = false;

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
        $this->record(new OrderWasPlaced($orderId));
    }

    /**
     * @param PlaceOrder $placeOrder
     * @CommandHandler(
     *     endpointId="orderReceiver",
     *     inputChannelName="order.register"
     * )
     * @return Order
     */
    public static function register(PlaceOrder $placeOrder) : self
    {
        return new self($placeOrder->getOrderId());
    }

    /**
     * @EventHandler(endpointId="orderPlaced")
     */
    public function notify(OrderWasPlaced $order) : void
    {
        $this->isNotified = true;
    }

    /**
     * @QueryHandler(inputChannelName="order.getOrder")
     */
    public function getRegisteredOrder() : string
    {
        return $this->orderId;
    }

    /**
     * @QueryHandler(inputChannelName="order.wasNotified")
     */
    public function getIsNotified() : bool
    {
        return $this->isNotified;
    }
}