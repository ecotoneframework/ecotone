<?php

namespace Ecotone\Api\Modelling;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class EventStream
{
    /**
     * @param Event[] $events
     */
    private function __construct(public readonly int $aggregateVersion, public readonly array $events)
    {
        foreach ($events as $event) {
            Assert::isTrue($event instanceof Event, sprintf('Event object is not type of Event, %s given', get_class($event)));
        }
    }

    /**
     * @param Event[] $events
     */
    public static function createWith(int $aggregateVersion, array $events): static
    {
        return new static($aggregateVersion, $events);
    }

    public static function createEmpty(): static
    {
        return new static(0, []);
    }
}
