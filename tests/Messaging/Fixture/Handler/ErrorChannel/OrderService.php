<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class OrderService
{
    private int $callCount = 0;

    private int $placedOrders = 0;

    #[CommandHandler('order.register', 'orderService')]
    #[Asynchronous(ErrorConfigurationContext::INPUT_CHANNEL)]
    public function order(string $orderName): void
    {
        $this->callCount += 1;

        if ($this->callCount > 3) {
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

    #[QueryHandler('getCallCount')]
    public function getCallCount(): int
    {
        return $this->callCount;
    }
}
