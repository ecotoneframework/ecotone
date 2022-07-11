<?php

namespace Test\Ecotone\Amqp\Fixture\ErrorChannel;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use InvalidArgumentException;

class OrderService
{
    private int $callCount = 0;

    private int $placedOrders = 0;

    #[CommandHandler('order.register', 'orderService')]
    #[Asynchronous(ErrorConfigurationContext::INPUT_CHANNEL)]
    public function order(string $orderName): void
    {
        $this->callCount += 1;

        if ($this->callCount > 2) {
            $this->placedOrders++;

            return;
        }

        throw new InvalidArgumentException('exception');
    }

    #[QueryHandler('getOrderAmount')]
    public function getOrder(): int
    {
        return $this->placedOrders;
    }
}
