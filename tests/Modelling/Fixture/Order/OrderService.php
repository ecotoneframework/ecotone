<?php


namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\LazyEventBus\LazyEventBus;

/**
 * @Asynchronous(channelName="orders")
 */
class OrderService
{
    /**
     * @var PlaceOrder[]
     */
    private $orders = [];
    /**
     * @var int[]
     */
    private $notifiedOrders = [];

    /**
     * @CommandHandler(
     *     inputChannelName="order.register",
     *     endpointId="orderReceiver"
     * )
     */
    public function register(PlaceOrder $placeOrder, EventBus $lazyEventBus) : void
    {
        $this->orders[] = $placeOrder;
        $lazyEventBus->sendWithMetadata(new OrderWasPlaced($placeOrder->getOrderId()), []);
    }

    /**
     * @EventHandler(endpointId="orderPlaced")
     */
    public function notify(OrderWasPlaced $orderWasPlaced) : void
    {
        $this->notifiedOrders[] = $orderWasPlaced->getOrderId();
    }

    /**
     * @QueryHandler(inputChannelName="order.getNotifiedOrders")
     */
    public function getNotifiedOrders() : array
    {
        return $this->notifiedOrders;
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