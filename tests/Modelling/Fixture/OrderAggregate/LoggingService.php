<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class LoggingService
{
    private $logging = [];

    #[Asynchronous('orders')]
    #[EventHandler(endpointId: 'loggingService')]
    public function log(OrderWasNotified $event): void
    {
        $this->logging[] = $event->getOrderId();
    }

    #[QueryHandler('getLogs')]
    public function getLoggedEvents(): array
    {
        return $this->logging;
    }
}
