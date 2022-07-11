<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Messaging\Attribute\ServiceActivator;
use InvalidArgumentException;

class OrderService
{
    #[ServiceActivator(ErrorConfigurationContext::INPUT_CHANNEL, 'orderService')]
    public function order(string $orderName): void
    {
        throw new InvalidArgumentException('exception');
    }
}
