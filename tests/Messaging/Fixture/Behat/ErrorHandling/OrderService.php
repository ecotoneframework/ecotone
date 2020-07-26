<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Messaging\Annotation\ServiceActivator;

class OrderService
{
    /**
     * @ServiceActivator(
     *     endpointId="orderService",
     *     inputChannelName=ErrorConfigurationContext::INPUT_CHANNEL
     * )
     */
    public function order(string $orderName) : void
    {
        throw new \InvalidArgumentException("exception");
    }
}