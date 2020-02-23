<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId\AddUserId;

/**
 * Class OrderService
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 * @AddUserId()
 */
class Order
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $orderId;

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param PlaceOrder $placeOrder
     * @CommandHandler(
     *     inputChannelName="order.register",
     *     endpointId="orderReceiver",
     *     poller=@Poller(handledMessageLimit=1, executionTimeLimitInMilliseconds=1)
     * )
     * @Async(channelName="orders")
     * @return Order
     */
    public static function register(PlaceOrder $placeOrder) : self
    {
        return new self($placeOrder->getPersonId());
    }

    /**
     * @QueryHandler(inputChannelName="order.getOrder")
     */
    public function getRegisteredOrder() : string
    {
        return $this->orderId;
    }
}