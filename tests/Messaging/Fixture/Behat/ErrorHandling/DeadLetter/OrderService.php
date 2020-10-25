<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class OrderService
{
    #[ServiceActivator(ErrorConfigurationContext::INPUT_CHANNEL, "orderService")]
    public function order(string $orderName) : void
    {
        throw new \InvalidArgumentException("exception");
    }
}