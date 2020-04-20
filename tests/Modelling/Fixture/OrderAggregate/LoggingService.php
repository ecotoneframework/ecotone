<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class LoggingService
{
    private $logging = [];

    /**
     * @EventHandler(endpointId="loggingService")
     * @Asynchronous("orders")
     */
    public function log(OrderWasNotified $event) : void
    {
        $this->logging[] = $event->getOrderId();
    }

    /**
     * @QueryHandler(inputChannelName="getLogs")
     */
    public function getLoggedEvents() : array
    {
        return $this->logging;
    }
}