<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Messaging\Annotation\ServiceActivator;

/**
 * @MessageEndpoint()
 */
class OrderService
{
    /**
     * @ServiceActivator(
     *     endpointId="orderService",
     *     inputChannelName=ErrorConfigurationContext::INPUT_CHANNEL,
     *     poller=@Poller(errorChannelName=ErrorConfigurationContext::ERROR_CHANNEL, executionTimeLimitInMilliseconds=1)
     * )
     */
    public function order(string $orderName) : void
    {
        throw new \InvalidArgumentException("exception");
    }
}