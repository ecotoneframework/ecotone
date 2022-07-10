<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class LoggingService
{
    private $logging = [];

    #[Asynchronous("orders")]
    #[EventHandler(endpointId: "loggingService")]
    public function log(OrderWasNotified $event) : void
    {
        $this->logging[] = $event->getOrderId();
    }

    #[QueryHandler("getLogs")]
    public function getLoggedEvents() : array
    {
        return $this->logging;
    }
}