<?php

namespace Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\CommandBus;

class OrderService
{
    private $order = null;

    #[CommandHandler('order.register')]
    public function register(string $order, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('makeOrder', $order);
    }

    #[Asynchronous('placeOrder')]
    #[CommandHandler('makeOrder', 'placeOrderEndpoint')]
    public function placeOrder(string $order): void
    {
        $this->eventBus->publish('orderWasPlaced');
        $this->order = $order;
    }

    #[QueryHandler('order.getOrder')]
    public function getOrder(): ?string
    {
        $order = $this->order;
        $this->order = null;

        return $order;
    }
}
