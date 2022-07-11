<?php

namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Asynchronous('orders')]
class OrderService
{
    /**
     * @var string[]
     */
    private $orders = [];

    #[CommandHandler('order.register', 'orderReceiver')]
    public function register(string $placeOrder): void
    {
        $this->orders[] = $placeOrder;
    }

    #[QueryHandler('order.getOrders')]
    public function getRegisteredOrders(): array
    {
        return $this->orders;
    }
}
