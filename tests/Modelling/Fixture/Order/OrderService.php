<?php

namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Gateway\EventBus;

#[Asynchronous('orders')]
/**
 * licence Apache-2.0
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

    #[CommandHandler(endpointId: 'registerOrderByClass')]
    #[CommandHandler('order.register', 'registerOrderByRouting')]
    public function register(PlaceOrder $placeOrder, EventBus $eventBus): void
    {
        $this->orders[] = $placeOrder;
        $eventBus->publish(new OrderWasPlaced($placeOrder->getOrderId()), []);
    }

    #[EventHandler(endpointId: 'notifyOrderWasPlaced')]
    public function notify(OrderWasPlaced $orderWasPlaced): void
    {
        $this->notifiedOrders[] = $orderWasPlaced->getOrderId();
    }

    #[QueryHandler('order.getNotifiedOrders')]
    public function getNotifiedOrders(): array
    {
        return $this->notifiedOrders;
    }

    #[QueryHandler('order.getOrders')]
    public function getRegisteredOrders(): array
    {
        return $this->orders;
    }
}
