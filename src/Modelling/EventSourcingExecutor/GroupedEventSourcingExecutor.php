<?php

declare(strict_types=1);

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Modelling\Event;

/**
 * licence Apache-2.0
 */
final class GroupedEventSourcingExecutor
{
    /**
     * @param array<string, EventSourcingHandlerExecutor> $eventSourcingExecutors
     */
    public function __construct(private array $eventSourcingExecutors)
    {

    }

    /**
     * @param Event[] $events
     */
    public function fillFor(string $aggregateClassName, ?object $aggregateInstance, array $events): object
    {
        $eventSourcingExecutor = $this->eventSourcingExecutors[$aggregateClassName];

        return $eventSourcingExecutor->fill($events, $aggregateInstance);
    }
}
