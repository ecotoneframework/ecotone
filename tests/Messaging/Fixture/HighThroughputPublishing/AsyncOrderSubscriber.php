<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\HighThroughputPublishing;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;

/**
 * licence Apache-2.0
 */
final class AsyncOrderSubscriber
{
    /** @var OrderWasPlaced[] */
    private array $receivedEvents = [];

    #[Asynchronous('async_orders')]
    #[EventHandler(endpointId: 'order_was_placed_subscriber')]
    public function handle(OrderWasPlaced $event): void
    {
        $this->receivedEvents[] = $event;
    }

    /**
     * @return string[]
     */
    public function getReceivedOrderIds(): array
    {
        return array_map(fn (OrderWasPlaced $event) => $event->orderId, $this->receivedEvents);
    }
}
