<?php


namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\EventBus;

#[Asynchronous("orders")]
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

    #[CommandHandler("order.register", "orderReceiver")]
    public function register(PlaceOrder $placeOrder, EventBus $eventBus) : void
    {
        $this->orders[] = $placeOrder;
        $eventBus->publish(new OrderWasPlaced($placeOrder->getOrderId()), []);
    }

    #[EventHandler(endpointId: "orderPlaced")]
    public function notify(OrderWasPlaced $orderWasPlaced) : void
    {
        $this->notifiedOrders[] = $orderWasPlaced->getOrderId();
    }

    #[QueryHandler("order.getNotifiedOrders")]
    public function getNotifiedOrders() : array
    {
        return $this->notifiedOrders;
    }

    #[QueryHandler("order.getOrders")]
    public function getRegisteredOrders() : array
    {
        return $this->orders;
    }
}