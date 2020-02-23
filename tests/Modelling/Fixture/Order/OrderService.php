<?php


namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class OrderService
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 * @Async(channelName="orders")
 */
class OrderService
{
    /**
     * @var PlaceOrder[]
     */
    private $orders = [];

    /**
     * @param PlaceOrder $placeOrder
     * @CommandHandler(
     *     inputChannelName="order.register",
     *     endpointId="orderReceiver"
     * )
     */
    public function register(PlaceOrder $placeOrder) : void
    {
        $this->orders[] = $placeOrder;
    }

    /**
     * @return array
     * @QueryHandler(inputChannelName="order.getOrders")
     */
    public function getRegisteredOrders() : array
    {
        return $this->orders;
    }
}