<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\HighThroughputPublishing;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\EventBus;

/**
 * licence Apache-2.0
 */
final class AsyncOrderForwarder
{
    public function __construct(private OperationsLog $operationsLog)
    {
    }

    #[Asynchronous('incoming_orders')]
    #[EventHandler(endpointId: 'order_request_forwarder')]
    public function forward(OrderRequestReceived $event, EventBus $eventBus): void
    {
        $this->operationsLog->log('consumer handler executed');
        $eventBus->publish(new OrderWasPlaced($event->order . '-forwarded'));
    }
}
