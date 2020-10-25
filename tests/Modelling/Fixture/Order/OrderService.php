<?php


namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
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
    public function register(PlaceOrder $placeOrder, EventBus $lazyEventBus) : void
    {
        $this->orders[] = $placeOrder;
        $lazyEventBus->sendWithMetadata(new OrderWasPlaced($placeOrder->getOrderId()), []);
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